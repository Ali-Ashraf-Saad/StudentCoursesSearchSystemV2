import re
import math
import os
from parsers import is_subject_line, parse_student_line, parse_subject_line

DATA_YEAR = os.environ.get("DATA_YEAR", "").strip()
if not re.fullmatch(r"\d{4}_\d{4}_[123]", DATA_YEAR):
    raise SystemExit("DATA_YEAR يجب أن يكون بصيغة YYYY_YYYY_1 أو YYYY_YYYY_2 أو YYYY_YYYY_3")

source_dir = os.path.join("txtData", DATA_YEAR)
department_files = [os.path.join(source_dir, name) for name in ("CS.txt", "IT.txt", "IS.txt", "gen.txt")]

STUDENTS_PER_ROOM = 30


def normalize_course_code(code: str) -> str:
    return code.strip().rstrip(".,;: ")


def count_students(lines, start_index):
    count = 0
    for i in range(start_index + 1, len(lines)):
        line = lines[i].strip()
        if is_subject_line(line):
            break
        if parse_student_line(line):
            count += 1
    return count


courses = []

for file_path in department_files:
    department_name = file_path.split("/")[-1].replace(".txt", "")
    with open(file_path, "r", encoding="utf-8") as f:
        lines = f.readlines()

    for i, line in enumerate(lines):
        line = line.strip()
        parsed_subject = parse_subject_line(line)
        if not parsed_subject:
            continue

        original_code, course_name = parsed_subject
        normalized_code = normalize_course_code(original_code)

        student_count = count_students(lines, i)
        room_count = max(1, math.ceil(student_count / STUDENTS_PER_ROOM))

        duplicated_codes = {"CS321", "IS352", "IS433", "CS482", "IT482", "IS482"}
        key = original_code
        if normalized_code in duplicated_codes and department_name in ["CS", "IT", "IS"]:
            key = f"{original_code}_{department_name}"

        courses.append((key, course_name, student_count, room_count))

# ---- كتابة الملف بالتنسيق الجديد ----
os.makedirs("draft", exist_ok=True)
output_path = os.path.abspath(os.path.join(source_dir, "rooms.txt"))

with open(output_path, "w", encoding="utf-8") as f:
    for i, (code, name, students, rooms) in enumerate(courses):
        f.write(f"اسم المادة: {name}\n")
        f.write(f"كود المادة: {code}\n")
        f.write(f"عدد الطلاب: {students}\n")
        f.write(f"عدد اللجان: {rooms}\n")
        if i < len(courses) - 1:
            f.write("\n")   # سطر فارغ بين المواد

# طباعة مسار الملف فقط في الكونسول
print(f"تم حفظ النتائج في: {output_path}")
