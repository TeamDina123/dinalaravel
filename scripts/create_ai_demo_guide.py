from pathlib import Path

from docx import Document
from docx.enum.section import WD_SECTION
from docx.enum.table import WD_ALIGN_VERTICAL, WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Inches, Pt, RGBColor


ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = ROOT / "output" / "doc"
OUT_DIR.mkdir(parents=True, exist_ok=True)
DOCX_PATH = OUT_DIR / "AI_Workout_Program_Builder_Customer_Demo_Guide.docx"

BLUE = "2E74B5"
DARK_BLUE = "1F4D78"
LIGHT_BLUE = "E8EEF5"
LIGHT_GRAY = "F2F4F7"
CALL_OUT = "F4F6F9"
GREEN = "147333"
GOLD = "7A5A00"
RED = "9B1C1C"


def set_cell_shading(cell, fill):
    tc_pr = cell._tc.get_or_add_tcPr()
    shd = tc_pr.find(qn("w:shd"))
    if shd is None:
        shd = OxmlElement("w:shd")
        tc_pr.append(shd)
    shd.set(qn("w:fill"), fill)


def set_cell_margins(cell, top=80, start=120, bottom=80, end=120):
    tc = cell._tc
    tc_pr = tc.get_or_add_tcPr()
    tc_mar = tc_pr.first_child_found_in("w:tcMar")
    if tc_mar is None:
        tc_mar = OxmlElement("w:tcMar")
        tc_pr.append(tc_mar)
    for m, v in {"top": top, "start": start, "bottom": bottom, "end": end}.items():
        node = tc_mar.find(qn(f"w:{m}"))
        if node is None:
            node = OxmlElement(f"w:{m}")
            tc_mar.append(node)
        node.set(qn("w:w"), str(v))
        node.set(qn("w:type"), "dxa")


def set_table_geometry(table, widths):
    table.alignment = WD_TABLE_ALIGNMENT.LEFT
    table.autofit = False
    tbl = table._tbl
    tbl_pr = tbl.tblPr
    tbl_w = tbl_pr.find(qn("w:tblW"))
    if tbl_w is None:
        tbl_w = OxmlElement("w:tblW")
        tbl_pr.append(tbl_w)
    tbl_w.set(qn("w:w"), str(sum(widths)))
    tbl_w.set(qn("w:type"), "dxa")

    tbl_ind = tbl_pr.find(qn("w:tblInd"))
    if tbl_ind is None:
        tbl_ind = OxmlElement("w:tblInd")
        tbl_pr.append(tbl_ind)
    tbl_ind.set(qn("w:w"), "120")
    tbl_ind.set(qn("w:type"), "dxa")

    grid = tbl.tblGrid
    for child in list(grid):
        grid.remove(child)
    for width in widths:
        col = OxmlElement("w:gridCol")
        col.set(qn("w:w"), str(width))
        grid.append(col)

    for row in table.rows:
        for idx, cell in enumerate(row.cells):
            cell.width = widths[idx]
            tc_pr = cell._tc.get_or_add_tcPr()
            tc_w = tc_pr.find(qn("w:tcW"))
            if tc_w is None:
                tc_w = OxmlElement("w:tcW")
                tc_pr.append(tc_w)
            tc_w.set(qn("w:w"), str(widths[idx]))
            tc_w.set(qn("w:type"), "dxa")
            cell.vertical_alignment = WD_ALIGN_VERTICAL.CENTER
            set_cell_margins(cell)


def style_doc(doc):
    section = doc.sections[0]
    section.top_margin = Inches(1)
    section.bottom_margin = Inches(1)
    section.left_margin = Inches(1)
    section.right_margin = Inches(1)
    section.header_distance = Inches(0.492)
    section.footer_distance = Inches(0.492)

    styles = doc.styles
    normal = styles["Normal"]
    normal.font.name = "Calibri"
    normal.font.size = Pt(11)
    normal.paragraph_format.space_after = Pt(6)
    normal.paragraph_format.line_spacing = 1.25

    for name, size, color, before, after in [
        ("Heading 1", 16, BLUE, 18, 10),
        ("Heading 2", 13, BLUE, 14, 7),
        ("Heading 3", 12, DARK_BLUE, 10, 5),
    ]:
        style = styles[name]
        style.font.name = "Calibri"
        style.font.size = Pt(size)
        style.font.color.rgb = RGBColor.from_string(color)
        style.font.bold = True
        style.paragraph_format.space_before = Pt(before)
        style.paragraph_format.space_after = Pt(after)
        style.paragraph_format.line_spacing = 1.25

    for name in ["List Bullet", "List Number"]:
        style = styles[name]
        style.font.name = "Calibri"
        style.font.size = Pt(11)
        style.paragraph_format.space_after = Pt(4)
        style.paragraph_format.line_spacing = 1.25
        style.paragraph_format.left_indent = Inches(0.375)
        style.paragraph_format.first_line_indent = Inches(-0.188)

    header = section.header.paragraphs[0]
    header.text = "Fitness with Dina - AI Workout Program Builder Demo Guide"
    header.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    header.runs[0].font.size = Pt(9)
    header.runs[0].font.color.rgb = RGBColor(90, 90, 90)

    footer = section.footer.paragraphs[0]
    footer.text = "Customer demonstration guide"
    footer.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    footer.runs[0].font.size = Pt(9)
    footer.runs[0].font.color.rgb = RGBColor(90, 90, 90)


def add_title(doc):
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(3)
    run = p.add_run("AI Workout Program Builder")
    run.font.name = "Calibri"
    run.font.size = Pt(26)
    run.font.bold = True
    run.font.color.rgb = RGBColor.from_string(DARK_BLUE)

    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(12)
    run = p.add_run("Customer Demo Guide and Technical Walkthrough")
    run.font.size = Pt(14)
    run.font.color.rgb = RGBColor(80, 80, 80)

    meta = doc.add_table(rows=5, cols=2)
    set_table_geometry(meta, [2700, 6660])
    rows = [
        ("Product", "Fitness with Dina App - AI-backed workout program generation system"),
        ("Audience", "Client/customer demonstration, admin team, coach reviewers, and delivery team"),
        ("Demo goal", "Show how the system turns approved exercise content and consultation data into safe, validated, assignable 12-week programs."),
        ("Current release status", "Eight implementation phases complete, plus launch-matrix dashboard polish."),
        ("Important positioning", "The system is AI-backed and rule-driven: it uses structured intelligence, generation rules, scoring, validation, and coach approval gates over an approved content library. It does not generate unsafe free-form workouts directly for clients."),
    ]
    for row, (label, value) in zip(meta.rows, rows):
        row.cells[0].text = label
        row.cells[1].text = value
        set_cell_shading(row.cells[0], LIGHT_BLUE)
        row.cells[0].paragraphs[0].runs[0].font.bold = True


def add_callout(doc, title, body, fill=CALL_OUT):
    table = doc.add_table(rows=1, cols=1)
    set_table_geometry(table, [9360])
    cell = table.cell(0, 0)
    set_cell_shading(cell, fill)
    p = cell.paragraphs[0]
    p.paragraph_format.space_after = Pt(2)
    r = p.add_run(title)
    r.bold = True
    r.font.color.rgb = RGBColor.from_string(DARK_BLUE)
    p2 = cell.add_paragraph(body)
    p2.paragraph_format.space_after = Pt(0)


def add_bullets(doc, items):
    for item in items:
        doc.add_paragraph(item, style="List Bullet")


def add_numbered(doc, items):
    for item in items:
        doc.add_paragraph(item, style="List Number")


def add_table(doc, headers, rows, widths):
    table = doc.add_table(rows=1, cols=len(headers))
    table.style = "Table Grid"
    set_table_geometry(table, widths)
    for idx, header in enumerate(headers):
        cell = table.rows[0].cells[idx]
        cell.text = header
        set_cell_shading(cell, LIGHT_BLUE)
        for p in cell.paragraphs:
            for r in p.runs:
                r.bold = True
                r.font.color.rgb = RGBColor.from_string(DARK_BLUE)
    for row_values in rows:
        cells = table.add_row().cells
        for idx, value in enumerate(row_values):
            cells[idx].text = str(value)
    set_table_geometry(table, widths)
    return table


def add_section(doc, title):
    doc.add_heading(title, level=1)


def add_subsection(doc, title):
    doc.add_heading(title, level=2)


def add_minor(doc, title):
    doc.add_heading(title, level=3)


def build_doc():
    doc = Document()
    style_doc(doc)
    add_title(doc)

    add_section(doc, "1. Executive Demo Summary")
    doc.add_paragraph(
        "This guide explains how to demonstrate the AI-backed workout program builder to a customer. "
        "It is written as both a demo script and a technical walkthrough, so you can explain what the user sees, what the admin/coach controls, where AI-backed logic is used, and how the system protects quality before anything reaches a client."
    )
    add_callout(
        doc,
        "Main customer message",
        "The system does not simply create random workouts. It audits exercise content, uses reviewed tags, generates structured workout days, builds complete multi-week programs, validates safety and consistency, recommends programs from consultation data, and lets a coach approve or assign the final program."
    )

    add_section(doc, "2. What the Customer Asked For")
    add_bullets(doc, [
        "A complete AI-backed program builder, not just a routine library.",
        "Beginner, intermediate, and advanced program support.",
        "Bodyweight, home dumbbell, gym, and full-gym equipment categories.",
        "English and Arabic launch programs.",
        "No-audio support as a future content track once enough no-audio exercise assets are available.",
        "12-week launch matrix programs with all seven days visible.",
        "Workout, rest, and active recovery days.",
        "Warm-up, cardio, mobility, main workout, core, lower-back, and stretching sections.",
        "Consultation-based recommendations and coach assignment.",
        "Validation for equipment, language, missing videos, missing sections, duration, progression, and safety.",
    ])

    add_section(doc, "3. Demo Setup Checklist")
    add_table(
        doc,
        ["Item", "What to prepare", "Why it matters"],
        [
            ("Local app", "Open the CMS at the local/admin URL and log in as an admin.", "The demonstration happens mainly in the CMS."),
            ("Routine Library", "Open Master Libraries > Routine Library.", "Shows audit, tagging, generation, review, and launch dashboard."),
            ("Programs", "Open Master Libraries > Programs.", "Shows the generated AI launch programs and seven-day schedule display."),
            ("Client profile", "Use a client with consultation answers, such as the test client used during smoke testing.", "Shows recommendation and assignment flow."),
            ("Known program", "Use Gym Strength & Hypertrophy or Dumbbell Express as examples.", "These were specifically checked during implementation."),
            ("Expectation", "Explain that English and Arabic launch programs are ready; no-audio is not in launch scope yet.", "Prevents confusion during customer questions."),
        ],
        [1700, 4300, 3360],
    )

    add_section(doc, "4. End-to-End Demo Story")
    doc.add_paragraph("Use this story when introducing the system before clicking through screens.")
    add_numbered(doc, [
        "We start with the exercise library. Every exercise needs structured tags: language, equipment, muscle group, movement pattern, intensity, impact, usage flags, safety notes, and generation approval.",
        "The system audits whether enough approved content exists for every required workout section and launch program category.",
        "The AI-backed routine generator creates complete workout days from approved exercises only.",
        "Coaches/admins review generated routines and approve or reject them.",
        "The launch matrix builder creates 12-week programs from approved generated routines.",
        "Each week has seven visible days with workout, rest, and active recovery labels.",
        "The validator checks safety and consistency before the program is treated as ready.",
        "Consultation recommendations match a client to the best launch programs and routines.",
        "The coach can override level, equipment, days, duration, or language, then assign the selected program.",
        "The app/API receives an AI schedule payload with weeks, days, sections, exercises, video links, prescriptions, and safety metadata.",
    ])

    add_section(doc, "5. Where AI Is Used")
    add_callout(
        doc,
        "Important wording for the customer",
        "In this release, AI-backed means structured generation, classification, scoring, validation, and recommendation logic built around approved content and coach review. The system intentionally avoids free-form hallucinated programs. It uses controlled intelligence so the output is explainable and reviewable."
    )
    add_table(
        doc,
        ["AI-backed area", "What it does", "Human control"],
        [
            ("Exercise auto-tagging", "Infers likely metadata from existing exercise data: muscle group, equipment, movement patterns, usage flags, intensity, impact, recommendations, and safety notes.", "Admin/coach must review and approve tags before generation."),
            ("Content audit", "Checks whether approved content covers each language, equipment category, level, and required workout section.", "Admin can see missing coverage before building anything."),
            ("Workout day generation", "Selects approved exercises and assembles them into complete structured workout days with warm-up, mobility, activation, main workout, core, cardio, and stretching sections.", "Generated routines enter review and can be approved, revised, or rejected."),
            ("Duration logic", "Estimates workout time and caps sets/exercise choices so 15, 20, 30, 45, and 60-minute routines stay realistic.", "Validator flags large duration mismatches."),
            ("Program builder", "Builds complete 12-week launch programs from approved routines and creates seven visible days per week.", "Admin can build one program, build the full matrix, or replace intentionally."),
            ("Progression logic", "Adds week-by-week progression blocks: technique/base volume, volume build, exercise progression, and density/performance.", "Validator checks that progression changes across blocks."),
            ("Safety validator", "Blocks or flags invalid schedules, missing sections, wrong equipment, wrong language, missing videos, excessive beginner impact, unsafe usage flags, and same-type high-intensity conflicts.", "Coach can fix content and rerun validation."),
            ("Recommendation engine", "Reads consultation signals and scores matching launch programs by language, level, equipment, days/week, duration, injury notes, and medical-clearance warnings.", "Coach can override recommendation inputs and decide what to assign."),
            ("Display payload", "Transforms generated schedules into app-ready week/day/section/exercise data.", "Frontend displays only the structured validated program data."),
        ],
        [1900, 4700, 2760],
    )

    add_section(doc, "6. Phase-by-Phase Demonstration")
    phase_rows = [
        ("1", "Exercise library audit and tagging", "Show exercise tags, filters, approved counts, missing content, and usage flags.", "Explain that AI generation is only as good as reviewed exercise metadata."),
        ("2", "Admin review workflow", "Show approve, reject, and needs-fix statuses.", "Explain coach governance: unapproved content cannot drive generation."),
        ("3", "Workout day generator", "Show Generate batch with language, equipment, level, duration, limit, and variations.", "Explain that the output is a full day with all required sections, not only a main workout."),
        ("4", "Multi-week program builder", "Explain that approved routines become a 12-week schedule with workout/rest/recovery days.", "Emphasize seven visible days per week."),
        ("5", "Launch matrix programs", "Show the 13-program matrix and English/Arabic states.", "Explain each matrix row maps to a client-ready program category."),
        ("6", "Consultation recommendation", "Open a client profile and generate recommendations.", "Explain matching logic and coach overrides."),
        ("7", "Validation and safety", "Open validation/error status and explain what is checked.", "Position validation as the safety layer before delivery."),
        ("8", "Frontend and app display", "Open a generated program and show week selector, day cards, sections, exercises, videos, and safety notes.", "Explain that mobile receives the same structured schedule payload."),
    ]
    add_table(doc, ["Phase", "Feature", "What to show", "Customer explanation"], phase_rows, [700, 2300, 3150, 3210])

    add_section(doc, "7. Screen-by-Screen Demo Script")
    add_subsection(doc, "Step 1 - Open Routine Library")
    add_bullets(doc, [
        "Navigate to CMS > Master Libraries > Routine Library.",
        "Point to the top filters: language, equipment, and level.",
        "Click Audit to refresh content readiness.",
        "Explain the Audit card: tagged exercises, approved exercises, untagged exercises, and missing required content.",
        "If there are missing rows, explain that the system will not silently generate weak programs. It tells the coach what content is missing.",
    ])
    add_subsection(doc, "Step 2 - Review Exercise Tags")
    add_bullets(doc, [
        "Scroll to Review exercise tags.",
        "Show the status tabs/counts: pending, needs fix, approved, rejected, generation.",
        "Select an exercise and show language, equipment, difficulty, exercise type, muscle group, impact, intensity, video variant, rest seconds, usage flags, and notes.",
        "Explain that usage flags decide which workout sections the exercise can serve: cardio warm-up, warm-up, mobility, lower-back activation, main workout, abs, obliques, lower-back strengthening, stretching.",
        "Click Approve only for content that is suitable for generation.",
    ])
    add_subsection(doc, "Step 3 - Generate Workout Routines")
    add_bullets(doc, [
        "Set language, equipment, and level filters first.",
        "Choose target minutes: 15, 20, 30, 45, or 60.",
        "Set limit and variations per type.",
        "Click Generate.",
        "Explain that generation uses approved tags only and creates structured routines with warm-up cardio, mobility, activation, preparation, main workout, core, optional cardio, and stretching.",
        "Review generated routines and approve them before launch programs use them.",
    ])
    add_subsection(doc, "Step 4 - Use Launch Matrix Dashboard")
    add_bullets(doc, [
        "In Routine Library, find Launch matrix dashboard.",
        "Show summary: Programs, Built valid, Ready to build, Invalid, Blocked.",
        "Show each matrix row: number, program name, level, equipment, days/week, minutes, English status, Arabic status.",
        "Point out content code, program ID, and day count under each language.",
        "Use Build on one language/program to rebuild or reuse a specific launch program.",
        "Use Build Matrix to build or verify all launch matrix programs.",
        "Use Replace only when intentionally regenerating existing launch programs.",
        "Use Errors if a validation failure appears.",
        "Use Open to jump directly to the program in Master Programs.",
    ])
    add_subsection(doc, "Step 5 - Open a Generated Program")
    add_bullets(doc, [
        "Open Master Libraries > Programs, or click Open from the launch dashboard.",
        "Choose a program such as Gym Strength & Hypertrophy or Dumbbell Express.",
        "In the program detail, show the AI Program Schedule panel.",
        "Use the Week tabs to switch weeks.",
        "Click day cards to show each day detail.",
        "Point out labels: Workout, Rest, Active Recovery.",
        "For workout days, show training focus, duration, muscle groups, progression notes, sections, exercises, video links, sets, reps, time, rest, notes, safety notes, and injury cautions.",
        "For rest/recovery days, show guidance instead of workout exercises.",
    ])
    add_subsection(doc, "Step 6 - Demonstrate Client Recommendation")
    add_bullets(doc, [
        "Open CMS > Clients and select a client with consultation answers.",
        "Open the Consultation tab.",
        "Show the AI Recommendation section.",
        "Click Generate to create a recommendation from the consultation.",
        "Explain calorie/BMR/TDEE output if enough profile data exists.",
        "Show Recommended Programs above Recommended Routines.",
        "Use coach overrides if needed: level, equipment, days, minutes, language.",
        "Explain medical or injury warnings if they appear.",
        "Click Assign on a recommended program.",
        "If the client already has an active program, explain the system blocks accidental replacement and asks for explicit replace confirmation.",
    ])
    add_subsection(doc, "Step 7 - Explain App/Mobile Behavior")
    add_bullets(doc, [
        "When a client opens a subscribed program, the app API returns the legacy week/workout data plus the new AI schedule payload.",
        "The AI schedule contains weeks, days, day types, selected workout, sections, exercises, videos, prescriptions, progression notes, and recovery guidance.",
        "Active programs follow the existing visibility rule: current week plus one week ahead.",
        "Completed programs can show all weeks.",
    ])

    add_section(doc, "8. Detailed Workflow Logic")
    add_table(
        doc,
        ["Stage", "Input", "Processing", "Output"],
        [
            ("Exercise tagging", "Exercise title, video, type, existing tags", "Auto-tagging suggests structured generation metadata; admin reviews it.", "Approved ExerciseLibraryTag rows."),
            ("Audit", "Approved exercise tags", "Coverage is checked by language, equipment, required usage, and launch program definition.", "Missing content report and launch readiness."),
            ("Routine generation", "Approved exercise pool and filters", "Generator selects section-appropriate exercises, estimates time, applies prescriptions, and stores metadata.", "Generated Workout rows with workout_exercises and routine_sections."),
            ("Routine validation", "Generated workout", "Validator checks required sections, metadata, duration tolerance, language, equipment, and prescription fields.", "Approved, revision, rejected, or pending-review routine status."),
            ("Launch build", "Launch matrix definition and approved generated routines", "Builder selects best routine spread, creates program, phase, phase routines, and AI week/day schedule.", "Program, ProgramPhase, ProgramPhaseWorkout, AiProgramWeekDay rows."),
            ("Program validation", "Program, week/day rows, workouts, exercise tags", "Validator checks schedule structure, safety, language, equipment, video presence, progression, and duration.", "Valid/invalid result shown in commands and dashboard."),
            ("Consultation recommendation", "User profile, consultation text, body stats, answers, settings", "Recommendation service infers goal, level, equipment, frequency, duration, injuries, language, and scores launch programs.", "Recommended program IDs, routines, calories, notes, warnings."),
            ("Assignment", "Selected recommended program", "Controller confirms it belongs to latest recommendation, checks program completeness, prevents accidental active-program conflict, then creates subscription tracking.", "ProgramSub, WeekWiseProgram, WeeklyWorkout tracking rows."),
            ("Display", "AI schedule rows and workout sections", "Display service transforms data into a stable payload for CMS and app.", "Week/day/section/exercise display contract."),
        ],
        [1500, 2300, 3600, 1960],
    )

    add_section(doc, "9. Launch Matrix to Demonstrate")
    matrix_rows = [
        ("1", "Bodyweight Foundations", "Beginner", "Bodyweight", "3", "25-30"),
        ("2", "Bodyweight Express", "Beginner", "Bodyweight", "3", "15"),
        ("3", "Dumbbell Home Foundations", "Beginner", "Dumbbells + bodyweight", "3", "30"),
        ("4", "Dumbbell Express", "Beginner", "Dumbbells + bodyweight", "3", "15"),
        ("5", "Gym Foundations", "Beginner", "Gym + dumbbells + bodyweight", "3", "45"),
        ("6", "Bodyweight Strength & Conditioning", "Intermediate", "Bodyweight", "4", "30"),
        ("7", "Bodyweight Express", "Intermediate", "Bodyweight", "4", "15"),
        ("8", "Dumbbell Strength & Shape", "Intermediate", "Dumbbells + bodyweight", "4", "30-45"),
        ("9", "Dumbbell Express", "Intermediate", "Dumbbells + bodyweight", "4", "15"),
        ("10", "Gym Strength & Shape", "Intermediate", "Gym + dumbbells + bodyweight", "4", "45-60"),
        ("11", "Bodyweight Performance", "Advanced", "Bodyweight", "5", "30-45"),
        ("12", "Dumbbell Hypertrophy", "Advanced", "Dumbbells + bodyweight", "5", "45"),
        ("13", "Gym Strength & Hypertrophy", "Advanced", "Full gym", "5-6", "60"),
    ]
    add_table(doc, ["No.", "Program", "Level", "Equipment", "Days", "Minutes"], matrix_rows, [600, 2750, 1300, 2800, 700, 1210])
    doc.add_paragraph("Current launch status: 13 English programs and 13 Arabic programs are built and valid. No-audio launch programs are not included until no-audio exercise content exists and is approved.")

    add_section(doc, "10. Validation and Safety Details")
    add_bullets(doc, [
        "Routine validation checks missing metadata, missing required sections, missing Phase 3 contract, duration mismatch, equipment violation, language mismatch, missing timed durations, and missing sets/reps.",
        "Program validation checks all seven days exist in every week.",
        "Workout days must reference workouts; rest and active recovery days must not reference workouts.",
        "Program phases must belong to the same program.",
        "Weekly workout count must match the launch matrix frequency.",
        "Each week must include at least one rest day.",
        "Program workouts must match allowed equipment, language, and level.",
        "Exercise rows must have video assignments.",
        "Exercise language and equipment must match the program rules.",
        "Beginner programs limit excessive high-impact content.",
        "High-intensity same-type routines are checked for consecutive-day conflicts.",
        "Progression notes must exist and change across training blocks.",
        "Injury-review usage flags are treated as unsafe for automatic program use.",
    ])

    add_section(doc, "11. What to Say About AI")
    add_table(
        doc,
        ["Customer question", "Recommended answer"],
        [
            ("Is this using AI?", "Yes. The system is AI-backed through structured classification, generation, scoring, validation, and recommendation logic. It uses the approved exercise library as the source of truth instead of producing uncontrolled free-form plans."),
            ("Can AI make unsafe programs?", "The design prevents that. Generated content must come from approved tags and approved routines, and the validator checks language, equipment, videos, sections, safety, duration, and schedule balance."),
            ("Can a coach control it?", "Yes. Coaches approve exercise tags and routines, can use needs-fix/reject statuses, can regenerate recommendations with overrides, and can choose whether to assign or replace a program."),
            ("Does it support Arabic?", "Yes. The launch matrix has English and Arabic built programs. Language matching is validated so Arabic programs use Arabic content."),
            ("What about no-audio?", "The system has no-audio as a planned content track, but launch programs are not built for no-audio until enough no-audio exercise content is available and approved."),
            ("Can it personalize programs?", "Yes. Consultation recommendations use client answers, user settings, body stats, training level, equipment, frequency, duration, injury notes, and medical-clearance signals to recommend best-fit programs."),
            ("Can we prove a program is complete?", "Yes. The launch matrix dashboard and validator show whether each program is built and valid. The verified matrix currently reports 26 built-valid English/Arabic launch programs."),
        ],
        [2600, 6760],
    )

    add_section(doc, "12. Technical Components to Mention")
    add_table(
        doc,
        ["Component", "Role in the system"],
        [
            ("RoutineExerciseAutoTaggerService", "Suggests structured exercise-library metadata for AI program generation."),
            ("RoutineContentAuditService", "Audits approved content and launch matrix readiness."),
            ("RoutineGeneratorService", "Creates structured workout-day routines from approved exercise tags."),
            ("RoutineValidatorService", "Validates generated routines before approval."),
            ("AiLaunchProgramBuilderService", "Builds complete 12-week launch programs from approved generated routines."),
            ("AiProgramSchedulePlannerService", "Defines weekly day templates and progression blocks."),
            ("AiProgramValidatorService", "Validates program-level safety, completeness, language, equipment, video, progression, and duration."),
            ("ConsultationRecommendationService", "Scores and stores recommended routines and programs for a client consultation."),
            ("AiProgramDisplayService", "Creates app/CMS-ready week/day/section/exercise display payloads."),
            ("RoutineLibraryController", "Provides CMS endpoints for audit, tagging, generation, review, launch dashboard, recommendation, and assignment."),
            ("ProgramsController", "Provides CMS program detail, phase detail, and AI schedule display data."),
            ("ProgramSubTrackingController", "Provides client app program subscription and program-detail behavior, including AI schedule payload."),
        ],
        [3100, 6260],
    )

    add_section(doc, "13. Main CMS and API Touchpoints")
    add_table(
        doc,
        ["Endpoint or command", "Purpose"],
        [
            ("GET /api/cms/routine-library/audit", "Load exercise coverage and launch readiness."),
            ("GET /api/cms/routine-library/exercise-tags", "Review exercise tags."),
            ("POST /api/cms/routine-library/exercise-tags/{id}/review", "Approve, reject, or mark an exercise tag as needs-fix."),
            ("POST /api/cms/routine-library/generate-batch", "Generate workout-day routine batches."),
            ("POST /api/cms/routine-library/routines/{id}/review", "Approve, reject, or revise generated routines."),
            ("GET /api/cms/routine-library/launch-matrix", "Load the launch matrix dashboard."),
            ("POST /api/cms/routine-library/launch-matrix/build", "Build one launch program or the full matrix."),
            ("GET /api/cms/get-program-detail/{id}", "Load CMS program detail and AI schedule display."),
            ("POST /api/cms/routine-library/recommendations/users/{userId}", "Generate consultation recommendations."),
            ("POST /api/cms/routine-library/recommendations/users/{userId}/assign-program", "Assign a recommended launch program."),
            ("GET /api/program-detail/{id}", "App/mobile program detail with legacy data plus ai_schedule."),
            ("php artisan ai-programs:build-launch-matrix --weeks=12", "CLI verification/build path for all launch programs."),
        ],
        [3700, 5660],
    )

    add_section(doc, "14. Customer Demo Talk Track")
    add_numbered(doc, [
        "Start with the problem: building safe programs manually takes time and consistency is hard across languages, levels, equipment, and durations.",
        "Show that the system begins with reviewed exercise content, not uncontrolled generation.",
        "Explain that audit identifies gaps before generation, which protects quality.",
        "Generate or review routines to show full workout-day structure.",
        "Open the launch matrix dashboard and point out that all English and Arabic launch programs are built and valid.",
        "Open a program and show the week selector and seven-day layout.",
        "Click workout days and show sectioned exercises, videos, prescriptions, progression, and safety notes.",
        "Click rest and active recovery days to show non-workout guidance.",
        "Open a client consultation and generate recommendations.",
        "Explain recommendation matching and coach overrides.",
        "Assign a recommended program and explain that tracking is created for the client.",
        "Close with the safety message: AI accelerates generation, but coach review and validation control what gets delivered.",
    ])

    add_section(doc, "15. Known Limits and How to Explain Them")
    add_bullets(doc, [
        "No-audio launch programs are not live yet because the system requires enough approved no-audio exercise content before building that track.",
        "Mobile UI must consume the new ai_schedule payload for the full seven-day display. The backend response is already available.",
        "Final client acceptance should include coach review of program naming, intensity, and business fit.",
        "The replace option intentionally exists but should be used carefully because it regenerates existing launch programs.",
        "This release focuses on controlled AI-backed generation, not free-form external model calls. That is a deliberate safety and quality choice.",
    ])

    add_section(doc, "16. Suggested Live Demo Order")
    add_table(
        doc,
        ["Order", "Screen", "Demo action", "Success signal"],
        [
            ("1", "Routine Library - Audit", "Click Audit and show coverage/readiness.", "Customer sees missing/ready state is transparent."),
            ("2", "Exercise tag review", "Open one exercise tag and show usage/safety fields.", "Customer sees coach-controlled AI inputs."),
            ("3", "Generate batch", "Explain generation settings; optionally generate a small batch.", "Customer sees routines are generated from filters."),
            ("4", "Routine review", "Approve or discuss approved routines.", "Customer sees review gate."),
            ("5", "Launch dashboard", "Show built-valid counts and program rows.", "Customer sees 26 valid launch programs."),
            ("6", "Program schedule", "Open a program and click weeks/days.", "Customer sees seven-day schedule and sections."),
            ("7", "Client recommendation", "Generate recommendation and show overrides.", "Customer sees personalization."),
            ("8", "Assignment", "Assign selected program.", "Customer sees operational handoff to client tracking."),
        ],
        [800, 2300, 3400, 2860],
    )

    add_section(doc, "17. Final Proof Points")
    add_bullets(doc, [
        "All eight planned phases are implemented.",
        "The launch matrix has 13 English and 13 Arabic programs.",
        "The matrix verification result is 26 built-valid programs, 0 invalid, 0 blocked.",
        "Program detail includes an AI schedule with seven days per week.",
        "Client recommendations can include full programs, not only individual routines.",
        "Coach overrides and assignment are available from the client popup.",
        "Validation exists at both routine and program levels.",
        "The launch dashboard gives a CMS alternative to command-line build/verification.",
    ])

    doc.add_page_break()
    add_section(doc, "Appendix A - Demo Checklist")
    checklist = [
        ("CMS login works", ""),
        ("Routine Library opens", ""),
        ("Audit loads counts", ""),
        ("Exercise tag editor opens", ""),
        ("Launch matrix dashboard shows 26 built valid", ""),
        ("Open button opens a generated program", ""),
        ("AI Program Schedule panel shows week tabs", ""),
        ("Workout day shows sections and exercises", ""),
        ("Rest/recovery day shows guidance", ""),
        ("Client consultation recommendation generates", ""),
        ("Recommended Programs section appears", ""),
        ("Assign Program action works or blocks active conflict correctly", ""),
    ]
    add_table(doc, ["Checklist item", "Pass/notes"], checklist, [4200, 5160])

    doc.save(DOCX_PATH)
    return DOCX_PATH


if __name__ == "__main__":
    print(build_doc())
