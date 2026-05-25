import os
import json
import re
#run second to generate json

# المجلدات المستهدفة
folders = ['foldersData/CS', 'foldersData/IT', 'foldersData/IS', 'foldersData/gen']

# قواميس لتجميع البيانات
students = {}       # id -> {name, courses_set}
courses = {}        # code -> {name, day, date, period, rooms: list}
exams = []          # list of exam dicts
rooms_set = set()   # جميع أسماء الغرف فريدة (لـ rooms.json)

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

        # السطر الأول: ( CS321 ) المقرر  الذكاء الاصطناعي
        # السماح بوجود نقطة في كود المادة مثل IS482.
        m = re.match(r'\(\s*([A-Za-z0-9.]+)\s*\)\s*المقرر\s*(.+)', lines[0])
        if not m:
            continue

        code = m.group(1)
        course_name = m.group(2).strip()

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
            if '\t' in stud_line:
                name, sid = stud_line.rsplit('\t', 1)
            else:
                parts_stud = stud_line.rsplit(maxsplit=1)
                if len(parts_stud) == 2:
                    name, sid = parts_stud
                else:
                    continue

            name = name.strip()
            sid = sid.strip()
            if not sid.isdigit():
                continue

            exam_student_ids.append(sid)

            if sid not in students:
                students[sid] = {
                    'name': name,
                    'courses': set()
                }

            students[sid]['courses'].add(code)

        # إضافة لجنة
        exams.append({
            'course': code,
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
os.makedirs('data', exist_ok=True)

with open('data/students.json', 'w', encoding='utf-8') as f:
    json.dump(students_list, f, ensure_ascii=False, indent=2)

with open('data/courses.json', 'w', encoding='utf-8') as f:
    json.dump(courses_dict, f, ensure_ascii=False, indent=2)

with open('data/exams.json', 'w', encoding='utf-8') as f:
    json.dump(exams, f, ensure_ascii=False, indent=2)

with open('data/rooms.json', 'w', encoding='utf-8') as f:
    json.dump(rooms_list, f, ensure_ascii=False, indent=2)

print("تم إنشاء الملفات في مجلد data:")
print("- students.json")
print("- courses.json")
print("- exams.json")
print("- rooms.json")