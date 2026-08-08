import json
import os
import re

DATA_YEAR = os.environ.get("DATA_YEAR", "").strip()
if not re.fullmatch(r"\d{4}_\d{4}_[12]", DATA_YEAR):
    raise SystemExit("DATA_YEAR يجب أن يكون بصيغة YYYY_YYYY_1 أو YYYY_YYYY_2")

# مجلد البيانات الخاص بالسنة والترم فقط
folder_path = os.path.join("txtData", DATA_YEAR)
files = ["CS.txt", "IT.txt", "IS.txt", "gen.txt"]

# استخراج كود واسم المادة، مع السماح بنقطة في الكود مثل IS482.
pattern = re.compile(r"^\(\s*([A-Za-z0-9xX.]+)\s*\)\s*المقرر\s*(.+?)\s*$")

# قراءة المواد أولًا لمعرفة الأكواد المتكررة بين الأقسام.
department_subjects = {}
code_departments = {}

for file_name in files:
    file_path = os.path.join(folder_path, file_name)
    if not os.path.isfile(file_path):
        continue

    department = os.path.splitext(file_name)[0]
    subjects = []
    seen = set()

    with open(file_path, "r", encoding="utf-8") as file:
        for line in file:
            match = pattern.match(line.strip())
            if not match:
                continue

            code = match.group(1).rstrip(".")
            name = match.group(2).strip()
            subject = (name, code)
            if subject in seen:
                continue

            seen.add(subject)
            subjects.append(subject)
            code_departments.setdefault(code, set()).add(department)

    department_subjects[file_name] = subjects

output_path = os.path.join(folder_path, "subjectsName.txt")
with open(output_path, "w", encoding="utf-8") as output_file:
    for file_name in files:
        subjects = department_subjects.get(file_name, [])
        if not subjects:
            continue

        department = os.path.splitext(file_name)[0]
        output_file.write(f"===== {file_name} =====\n")

        for name, code in subjects:
            output_code = code
            if len(code_departments.get(code, set())) > 1:
                output_code = f"{code}_{department}"

            output_file.write(
                f"    {json.dumps(name, ensure_ascii=False)}: "
                f"{json.dumps(output_code, ensure_ascii=False)},\n"
            )

        output_file.write("\n")

print(f"تم إنشاء الملف: {output_path}")
