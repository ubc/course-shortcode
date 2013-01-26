course-shortcode
================
This shortcode allows the listing of UBC courses with data obtained directly from the UBC Calendar. Some of the allowed parameters are as below:

department (default ” – if left empty will show example of usage)
course (default ” – if department filled and course empty then you get all depatmental courses listed)
tabs (default false – if true, the data is setup with tabs for each level of courses e.g. 100 year level, 200 year level etc)
pills (default false – if true, the data is setup with pills for each level of courses e.g. 100 year level, 200 year level etc)
tabcount (default 4 – if true, the data is truncated  at the level level of courses e.g. 400 year level)
pillcount (default 4 – if true, the data is truncated  at the level level of courses e.g. 400 year level)

Usage examples:

[ubccourses department=ANTH course=201A]
[ubccourses department=ANTH pills=true]
[ubccourses department=PSYC tabs=true]
[ubccourses department=ECON pills=true pillcount=6]
[ubccourses department=ANTH]

