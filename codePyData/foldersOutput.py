# -*- coding: utf-8 -*-
#run first to generate folders
import os
import re

LINES_PER_FILE = 30
DATA_YEAR = os.environ.get("DATA_YEAR", "").strip()
if not re.fullmatch(r"\d{4}_\d{4}_[12]", DATA_YEAR):
    raise SystemExit("DATA_YEAR يجب أن يكون بصيغة YYYY_YYYY_1 أو YYYY_YYYY_2")

FOLDERS_OUTPUT_ROOT = os.path.join("foldersData", DATA_YEAR)
source_dir = os.path.join("txtData", DATA_YEAR)
department_files = [os.path.join(source_dir, name) for name in ("CS.txt", "IT.txt", "IS.txt", "gen.txt")]


# بيانات الجدول الخاصة بالسنة/الترم المختار
import runpy

schedule_path = os.path.join(source_dir, "schedule.py")
if not os.path.isfile(schedule_path):
    raise SystemExit(f"ملف الجدول غير موجود: {schedule_path}")

schedule_data = runpy.run_path(schedule_path)
try:
    raw_exam_schedule = schedule_data["raw_exam_schedule"]
    raw_room_schedule = schedule_data["raw_room_schedule"]
except KeyError as error:
    raise SystemExit(f"المتغير غير موجود داخل {schedule_path}: {error.args[0]}")


def clean_filename(name):
    for ch in r'<>:"/\\|?*':
        name = name.replace(ch, "-")
    return name.strip()

def normalize_text(text):
    text = text.strip()
    text = text.replace("أ", "ا").replace("إ", "ا").replace("آ", "ا")
    text = text.replace("ى", "ي").replace("ة", "ه")
    text = text.replace("ـ", "")
    text = re.sub(r'[\u064B-\u0652]', '', text)
    text = re.sub(r'\s+', ' ', text)
    return text.lower().strip()

def extract_subject_name(subject_line):
    return re.sub(r'^\(\s*[A-Za-z0-9.]+\s*\)\s*المقرر\s*', '', subject_line).strip()

def extract_subject_code(subject_line):
    m = re.search(r'\(\s*([A-Za-z0-9.]+)\s*\)', subject_line)
    return m.group(1).rstrip('.') if m else "UNKNOWN"

# تطبيع المفاتيح
exam_schedule = {normalize_text(k): v for k, v in raw_exam_schedule.items()}
room_schedule = {normalize_text(k): v for k, v in raw_room_schedule.items()}

def get_department_suffix(department_name):
    department_name = department_name.upper()
    if department_name in ["CS", "IT", "IS"]:
        return department_name
    return None

def get_department_specific_key(base_code, department_name):
    dept_suffix = get_department_suffix(department_name)
    if dept_suffix:
        return normalize_text(f"{base_code}_{dept_suffix}")
    return None

def lookup_with_department_preference(schedule_dict, base_code, department_name, default_value):
    """
    يبحث أولاً عن:
    1) code_DEPARTMENT
    2) code
    ثم يرجع default_value
    """
    dept_key = get_department_specific_key(base_code, department_name)
    if dept_key and dept_key in schedule_dict:
        return schedule_dict[dept_key]

    base_key = normalize_text(base_code)
    return schedule_dict.get(base_key, default_value)

for department_file in department_files:
    department_name = os.path.splitext(os.path.basename(department_file))[0]

    # إنشاء مجلد داخل foldersData
    output_dir = os.path.join(FOLDERS_OUTPUT_ROOT, department_name)
    os.makedirs(output_dir, exist_ok=True)

    with open(department_file, "r", encoding="utf-8") as f:
        lines = [line.rstrip() for line in f]

    subjects = []
    current_subject = None
    current_students = []

    for line in lines:
        stripped = line.strip()
        if not stripped:
            continue

        if re.match(r'^\(\s*[A-Za-z0-9.]+\s*\)', stripped):
            if current_subject:
                subjects.append((current_subject, current_students))
            current_subject = stripped
            current_students = []
        else:
            current_students.append(stripped)

    if current_subject:
        subjects.append((current_subject, current_students))

    for subject_line, students in subjects:
        subject_code = extract_subject_code(subject_line)
        subject_name = extract_subject_name(subject_line)

        # نستخدم الكود مع أولوية القسم
        exam_day, exam_date, exam_time = lookup_with_department_preference(
            exam_schedule,
            subject_code,
            department_name,
            ("غير معروف", "غير معروف", "غير معروف")
        )

        room_list = lookup_with_department_preference(
            room_schedule,
            subject_code,
            department_name,
            []
        )

        # كل مادة تبدأ من لجنة 1
        for committee_number, start in enumerate(range(0, len(students), LINES_PER_FILE), start=1):
            chunk = students[start:start + LINES_PER_FILE]

            output_filename = f"{subject_code}_{committee_number}.txt"
            output_filename = clean_filename(output_filename)
            output_path = os.path.join(output_dir, output_filename)

            if committee_number - 1 < len(room_list):
                room_number = room_list[committee_number - 1]
                # تعديلات خاصة للقيم -1 و 0
                if room_number == -1:
                    committee_line = f"لجنة {committee_number} الصالة أعلى مدرج 5"
                elif room_number == 0:
                    committee_line = f"لجنة {committee_number} الصالة أمام الخزينة"
                elif room_number == -2:
                    committee_line = f'لجنة {committee_number} <a class="location-link" href="images/location.jpg" target="_blank" rel="noopener noreferrer">اضغط هنا</a>'
                else:
                    room_type = "معمل" if room_number > 100 else "مدرج"
                    committee_line = f"لجنة {committee_number} {room_type} {room_number}"
            else:
                committee_line = f"المكان غير محدد"

            with open(output_path, "w", encoding="utf-8") as out:
                # عند الطبعة نكتب الاسم، وليس الكود
                out.write(subject_line + "\n")
                out.write(committee_line + "\n")
                out.write(f"{exam_day} {exam_date}\n")
                out.write(f"{exam_time}\n\n")
                out.write("\n".join(chunk))

            print(f"تم إنشاء: {output_path}")

print("\nتم الانتهاء بنجاح.")
