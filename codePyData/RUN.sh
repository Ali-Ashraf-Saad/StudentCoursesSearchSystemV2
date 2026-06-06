#!/bin/bash
# bash RUN.sh
cd ..
rm -rf data/courses.json data/exams.json data/rooms.json data/students.json foldersData txtData/subjects.txt txtData/All.txt txtData/rooms.txt 
python3 -u "codePyData/AllCode.py"
python3 -u "codePyData/rooms.py"
python3 -u "codePyData/subject.py"
python3 -u "codePyData/foldersOutput.py"
python3 -u "codePyData/jsonOutput.py"
