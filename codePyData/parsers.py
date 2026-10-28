import re


_SUBJECT_PATTERN = re.compile(
    r"^\s*(?:"
    r"\(\s*(?P<prefix_code>[A-Za-z0-9xX.]+)\s*\)\s*المقرر\s*"
    r"(?P<prefix_name>.*?)"
    r"|"
    r"المقرر\s*(?P<suffix_name>.*?)\s*"
    r"\(\s*(?P<suffix_code>[A-Za-z0-9xX.]+)\s*\)"
    r")\s*$"
)


def parse_subject_line(line):
    """Return (course_code, course_name) for either supported subject format."""
    match = _SUBJECT_PATTERN.match(line)
    if not match:
        return None

    code = match.group("prefix_code") or match.group("suffix_code")
    name = match.group("prefix_name") or match.group("suffix_name")
    # النقطة اللاحقة جزء من كود بعض المقررات (مثل CS482. للمشروع2)،
    # لذلك لا نحذفها حتى لا يندمج المقرر مع CS482 (المشروع).
    return code.strip(), name.strip()


def is_subject_line(line):
    return parse_subject_line(line) is not None


def parse_student_line(line):
    """Return (student_name, student_id) for either name/id ordering."""
    text = line.strip()
    fields = [field.strip() for field in text.split("\t") if field.strip()]

    if len(fields) >= 2:
        first, last = fields[0], fields[-1]
        if first.isdigit():
            return last, first
        if last.isdigit():
            return first, last

    match = re.match(r"^(\d+)\s+(.+)$", text)
    if match:
        return match.group(2).strip(), match.group(1)

    match = re.match(r"^(.+?)\s+(\d+)$", text)
    if match:
        return match.group(1).strip(), match.group(2)

    return None
