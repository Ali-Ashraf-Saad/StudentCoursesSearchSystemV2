import os
import re

# المجلد الذي يحتوي على الملفات
folder_path = "txtData"

# الملفات المطلوبة
files = ["CS.txt", "IT.txt", "IS.txt", "gen.txt"]

# Regex لاستخراج سطر المادة
pattern = re.compile(r"\(\s*[A-Za-z0-9xX]+\s*\).*")

# ملف الإخراج
output_path = os.path.join(folder_path, "subjects.txt")

with open(output_path, "w", encoding="utf-8") as output_file:

    for file_name in files:
        file_path = os.path.join(folder_path, file_name)

        # التأكد أن الملف موجود
        if not os.path.exists(file_path):
            continue

        # كتابة اسم الملف
        output_file.write(f"===== {file_name} =====\n")

        subjects = []
        seen = set()

        with open(file_path, "r", encoding="utf-8") as file:
            for line in file:
                line = line.strip()

                # إذا كان السطر يحتوي على مادة
                if pattern.match(line):
                    
                    # منع التكرار مع الحفاظ على الترتيب
                    if line not in seen:
                        seen.add(line)
                        subjects.append(line)

        # كتابة المواد بنفس ترتيب الملف
        for subject in subjects:
            output_file.write(subject + "\n")

        output_file.write("\n")  # سطر فارغ بين كل ملف والثاني

print(f"تم إنشاء الملف: {output_path}")