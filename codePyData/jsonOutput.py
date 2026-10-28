import os
import json
import re
import runpy
from parsers import parse_student_line, parse_subject_line
# run second to generate json

DATA_YEAR = os.environ.get("DATA_YEAR", "").strip()
if not re.fullmatch(r"\d{4}_\d{4}_[123]", DATA_YEAR):
    raise SystemExit("DATA_YEAR يجب أن يكون بصيغة YYYY_YYYY_1 أو YYYY_YYYY_2 أو YYYY_YYYY_3")

folders_root = os.path.join("foldersData", DATA_YEAR)
output_root = os.path.join("data", DATA_YEAR)

# المجلدات المستهدفة
folders = [os.path.join(folders_root, department) for department in ['CS', 'IT', 'IS', 'gen']]

# قواميس لتجميع البيانات
students = {}       # id -> {name, courses_set}
courses = {}        # code -> {name, day, date, period, rooms: list}
exams = []          # list of exam dicts
rooms_set = set()   # جميع أسماء الغرف فريدة (لـ rooms.json)

# عدد السكاشن لكل مادة. يمكن وضع مفتاح خاص بالقسم مثل CS362_CS،
# وإلا فسيُستخدم المفتاح العام CS362.
schedule_path = os.path.join('txtData', DATA_YEAR, 'schedule.py')
schedule_data = runpy.run_path(schedule_path)
raw_section_schedule = schedule_data.get('raw_section_schedule', {})

def normalize_schedule_key(value):
    return re.sub(r'\s+', '', str(value).strip().lower())

section_schedule = {
    normalize_schedule_key(key): max(1, int(value))
    for key, value in raw_section_schedule.items()
}

def get_section_count(course_code, department):
    department_key = normalize_schedule_key(f'{course_code}_{department}')
    return section_schedule.get(department_key, section_schedule.get(normalize_schedule_key(course_code)))

# ترتيب الطلاب في كل مادة/قسم، لاستخدامه في توزيع السكاشن.
course_students_order = {}

def parse_room_info(room_text):
    """
    تستقبل النص مثل "لجنة 1 مدرج 3" أو "لجنة 5 معمل 301" أو
    "لجنة 1 الصالة أعلى مدرج 5" (رقم اللجنة -1) أو 
    "لجنة 1 الصالة أمام الخزينة" (رقم اللجنة 0)
    وتعيد اسم الغرفة ورقمها بعد تطبيق القواعد.
    """
    # التعامل مع الصالات الخاصة أولاً
    match_special = re.match(r'لجنة\s+\d+\s+(الصالة أعلى مدرج 5)', room_text)
    if match_special:
        return match_special.group(1), -1

    match_special0 = re.match(r'لجنة\s+\d+\s+(الصالة أمام الخزينة)', room_text)
    if match_special0:
        return match_special0.group(1), 0

    match_location_link = re.match(r'لجنة\s+\d+\s+<a\b[^>]*>\s*(اضغط هنا)\s*</a>', room_text)
    if match_location_link:
        return match_location_link.group(1), -2

    # الحالة العادية: مدرج أو معمل + رقم
    match = re.search(r'(مدرج|معمل)\s*(\d+)', room_text)
    if match:
        room_type = match.group(1)
        room_number = int(match.group(2))
        if room_number > 100:
            return f"معمل {room_number}", room_number
        else:
            return f"{room_type} {room_number}", room_number
    else:
        # نص غير معروف (نادر)
        return room_text, None

for folder in folders:
    if not os.path.isdir(folder):
        continue

    department = os.path.basename(folder)

    # ترتيب الملفات لضمان ترتيب اللجان تصاعدياً
    filenames = sorted([f for f in os.listdir(folder) if f.endswith('.txt')])
    for filename in filenames:
        filepath = os.path.join(folder, filename)
        base = filename[:-4]
        parts = base.split('_')
        if len(parts) != 2:
            continue

        course_code, committee_str = parts[0], parts[1]
        try:
            committee = int(committee_str)
        except ValueError:
            continue

        with open(filepath, 'r', encoding='utf-8') as f:
            lines = [line.strip() for line in f if line.strip()]

        if len(lines) < 4:
            continue

        # السطر الأول قد يكون: ( CS321 ) المقرر الذكاء الاصطناعي
        # أو: المقرر الذكاء الاصطناعي ( CS321 )
        parsed_subject = parse_subject_line(lines[0])
        if not parsed_subject:
            continue

        code, course_name = parsed_subject

        # السطر الثاني: معالجة الغرفة (يدعم -1 و 0)
        room_name, room_number = parse_room_info(lines[1])
        rooms_set.add(room_name)

        # السطر الثالث: الاثنين 8/6/2026
        day_date = lines[2].split()
        day = day_date[0] if day_date else ''
        date = day_date[1] if len(day_date) > 1 else ''

        # السطر الرابع: الفترة الثانية (12:00 - 2:00)
        period_time = lines[3]
        period_match = re.match(r'(الفترة \S+)', period_time)
        period = period_match.group(1) if period_match else period_time
        time_match = re.search(r'\((.*?)\)', period_time)
        time = time_match.group(1) if time_match else ''

        # الطلاب
        student_lines = lines[4:]
        exam_student_ids = []
        for stud_line in student_lines:
            student = parse_student_line(stud_line)
            if not student:
                continue

            name, sid = student

            exam_student_ids.append(sid)
            course_students_order.setdefault((department, code), []).append(sid)

            if sid not in students:
                students[sid] = {
                    'name': name,
                    'courses': set()
                }

            students[sid]['courses'].add(code)

        # إضافة لجنة
        exams.append({
            'course': code,
            'department': department,
            'committee': committee,
            'room': room_name,
            'day': day,
            'date': date,
            'period': period,
            'time': time,
            'students': exam_student_ids
        })

        # تحديث المادة: نضيف رقم الغرفة (بما في ذلك -1 أو 0)
        if code not in courses:
            courses[code] = {
                'name': course_name,
                'day': day,
                'date': date,
                'period': period,
                'rooms': []
            }

        if room_number is not None:
            courses[code]['rooms'].append(room_number)

# توزيع الطلاب بالتساوي قدر الإمكان: 71 طالبًا و2 سكشن => 35 ثم 36.
section_by_student = {}
for (department, code), student_ids in course_students_order.items():
    section_count = get_section_count(code, department)
    if section_count is None:
        continue
    students_per_section = max(1, len(student_ids) // section_count)
    for index, sid in enumerate(student_ids):
        section_number = min(index // students_per_section + 1, section_count)
        section_by_student[(department, code, sid)] = section_number

for exam in exams:
    sections = {
        sid: section_by_student.get((exam['department'], exam['course'], sid), 1)
        for sid in exam['students']
        if (exam['department'], exam['course'], sid) in section_by_student
    }
    if sections:
        exam['sections'] = sections

# تجهيز المخرجات النهائية
students_list = []
for sid, data in students.items():
    students_list.append({
        'id': sid,
        'name': data['name'],
        'courses': sorted(list(data['courses']))
    })

courses_dict = {}
for code, data in courses.items():
    courses_dict[code] = {
        'name': data['name'],
        'day': data['day'],
        'date': data['date'],
        'period': data['period'],
        'rooms': data['rooms']
    }

rooms_list = sorted(list(rooms_set))

# كتابة الملفات
os.makedirs(output_root, exist_ok=True)

with open(os.path.join(output_root, 'students.json'), 'w', encoding='utf-8') as f:
    json.dump(students_list, f, ensure_ascii=False, indent=2)

with open(os.path.join(output_root, 'courses.json'), 'w', encoding='utf-8') as f:
    json.dump(courses_dict, f, ensure_ascii=False, indent=2)

with open(os.path.join(output_root, 'exams.json'), 'w', encoding='utf-8') as f:
    json.dump(exams, f, ensure_ascii=False, indent=2)

with open(os.path.join(output_root, 'rooms.json'), 'w', encoding='utf-8') as f:
    json.dump(rooms_list, f, ensure_ascii=False, indent=2)

print(f"تم إنشاء الملفات في مجلد {output_root}:")
print("- students.json")
print("- courses.json")
print("- exams.json")
print("- rooms.json")
