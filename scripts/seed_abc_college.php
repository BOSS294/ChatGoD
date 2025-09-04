<?php
// scripts/seed_abc_college.php
declare(strict_types=1);
require_once __DIR__ . '/../Connectors/connector.php';
use ChatGoD\Connector;

function uuid_v4_simple(){
    // Reuse connector uuid_v4 if available; fallback quick v4
    if (function_exists('ChatGoD\\Connector\\uuid_v4')) return \ChatGoD\Connector\uuid_v4();
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

try {
    $pdo = Connector\db_connect();
    $pdo->beginTransaction();

    $clgId = '11111111-aaaa-4aaa-8aaa-111111111111';

    // remove existing college_data rows for ABC
    $del = $pdo->prepare("DELETE FROM college_data WHERE CLG_ID = :clg");
    $del->execute([':clg' => $clgId]);

    // remove existing QA suggestions for ABC
    $del2 = $pdo->prepare("DELETE FROM college_qa_suggestions WHERE CLG_ID = :clg");
    $del2->execute([':clg' => $clgId]);

    // BASIC record (rich JSON)
    $basic = [
      "name"=>"ABC College of Engineering",
      "short_description"=>"Premier engineering institution offering BE, ME and diploma programmes with strong placement record and industry ties.",
      "description"=>"ABC College of Engineering — established in 1998 — is NAAC A accredited autonomous institution offering UG/PG programs across Computer, Electronics, Mechanical, Civil and allied disciplines. Focus: applied AI, embedded systems, sustainable engineering and industry collaborations.",
      "detailed_description"=>"Founded in 1998, ABC College has a practice-led curriculum, modern labs (AI/ML, IoT & Robotics), a 60k+ digital library, entrepreneurship cell and a strong placement & training cell. MOUs with top companies provide internships and live projects.",
      "established"=>"1998",
      "accreditation"=>["naac"=>"A","nba"=>["Computer Engineering","Electronics Engineering"],"autonomous_since"=>"2018"],
      "rankings"=>["state_rank"=>5,"national_private_rank"=>72],
      "address"=>"123 College Road, City, State, 400001",
      "gps"=>["lat"=>19.075983,"lon"=>72.877655],
      "phone"=>"+91-22-12345678",
      "email"=>"info@abccollege.edu",
      "website"=>"https://www.abccollege.edu",
      "campus_size_acres"=>25,
      "hostel_capacity"=>800,
      "library"=>["books"=>60000,"journals"=>1200,"e_resources"=>true],
      "labs_summary"=>["AI & ML Lab","IoT & Embedded Systems Lab","Advanced Electronics Lab","Mechanical Workshops","Civil Materials Lab"],
      "placement_stats"=>["average_lpa"=>6.2,"median_lpa"=>4.8,"highest_lpa"=>22.0,"placement_rate_pct"=>78],
      "top_recruiters"=>["TechCorp","BuildIt","SoftServe","FinData","GreenEnergy"],
      "scholarships"=>["Merit scholarships (top 5% UG)","Need-based grants","Company-sponsored fellowships"],
      "admissions"=>["modes"=>["Merit","State CET","Institute Entrance"], "important_dates"=>["applications_open"=>"2026-05-01","admissions_close"=>"2026-07-15"]],
      "contact_persons"=>[
        ["name"=>"Dr. Ravi Menon","role"=>"Principal","email"=>"principal@abccollege.edu","phone"=>"+91-22-12345000"],
        ["name"=>"Ms. Neha Sharma","role"=>"Placement Head","email"=>"placements@abccollege.edu","phone"=>"+91-22-12345011"]
      ],
      "faqs"=>[
        ["q"=>"How do I apply?","a"=>"Apply online through the Admissions section. Required: 10th/12th mark sheets, ID proof, passport photo. Fee: ₹500."],
        ["q"=>"Does ABC provide hostel?","a"=>"Yes — separate hostels for boys and girls. Hostel fees: approx ₹40,000/yr."],
        ["q"=>"What are the placement highlights?","a"=>"Average 6.2 LPA; top companies include TechCorp and BuildIt."]
      ],
      "suggestions"=>["Ask about placements","Ask about fees","Ask about hostel facilities","Ask about AI lab & internships"]
    ];

    $courses = [
      "courses"=>[
        ["level"=>"UG","name"=>"B.E. Computer Engineering","duration"=>"4 years","seats"=>120,"avg_annual_fees"=>"60000","eligibility"=>"10+2 PCM, CET/Merit","specializations"=>["AI & ML","Data Science","Cybersecurity"]],
        ["level"=>"UG","name"=>"B.E. Electronics Engineering","duration"=>"4 years","seats"=>60,"avg_annual_fees"=>"55000","eligibility"=>"10+2 PCM"],
        ["level"=>"PG","name"=>"M.E. Computer Engineering (Software)","duration"=>"2 years","seats"=>18,"avg_annual_fees"=>"70000","eligibility"=>"B.E. CS/IT or equivalent"],
        ["level"=>"Diploma","name"=>"Diploma in Mechanical Engineering","duration"=>"3 years","seats"=>60,"avg_annual_fees"=>"30000"]
      ],
      "admission_mode"=>"Merit / State CET / Institute Entrance",
      "industry_projects"=>"Capstone projects mandatory with industry mentors"
    ];

    $departments = [
      "departments"=>[
        ["name"=>"Computer Engineering","hod"=>"Dr. S. K. Patel","faculty_count"=>42,"labs"=>["AI & ML Lab","Systems Lab"]],
        ["name"=>"Electronics Engineering","hod"=>"Dr. A. R. Kulkarni","faculty_count"=>28,"labs"=>["VLSI Lab","Embedded Lab"]],
        ["name"=>"Mechanical Engineering","hod"=>"Dr. M. Iyer","faculty_count"=>34,"labs"=>["CAD/CAM Lab","Thermal Lab"]],
        ["name"=>"Civil Engineering","hod"=>"Dr. P. S. Rao","faculty_count"=>20,"labs"=>["Concrete Lab","Geotech Lab"]]
      ]
    ];

    $other = [
      "placements"=>["average_lpa"=>6.2,"median_lpa"=>4.8,"highest_lpa"=>22.0,"placement_rate_pct"=>78],
      "infrastructure"=>["hostel"=>"Capacity 800, warden-run, Wi-Fi","sports"=>"Football, Cricket, Badminton, Gym","healthcare"=>"On-campus medical centre"],
      "research_incubation"=>"MoUs with TechCorp R&D; annual tech conclave 'ABCTech'"
    ];

    // prepare insert statement (columns aligned with new schema)
    $ins = $pdo->prepare("INSERT INTO college_data
      (DATAID, CLG_ID, DATA_TYPE, CLG_BASIC, CLG_LOCATIONS, CLG_COURSES, CLG_FEES, CLG_DEPARTMENTS, KEYWORDS, SEARCH_TEXT, INFO_ADDED_BY, INFO_ADDED_ON, DATA_VERSION, DATA_STATUS, SOURCE)
      VALUES (:DATAID, :CLG_ID, :DATA_TYPE, :CLG_BASIC, :CLG_LOCATIONS, :CLG_COURSES, :CLG_FEES, :CLG_DEPARTMENTS, :KEYWORDS, :SEARCH_TEXT, :INFO_ADDED_BY, NOW(), :DATA_VERSION, :DATA_STATUS, :SOURCE)
    ");

    // BASIC row
    $ins->execute([
      ':DATAID'=>uuid_v4_simple(),
      ':CLG_ID'=>$clgId,
      ':DATA_TYPE'=>'BASIC',
      ':CLG_BASIC'=>json_encode($basic, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
      ':CLG_LOCATIONS'=>json_encode([["campus"=>"Main Campus","address"=>"123 College Road"]]),
      ':CLG_COURSES'=>null,
      ':CLG_FEES'=>null,
      ':CLG_DEPARTMENTS'=>null,
      ':KEYWORDS'=>json_encode(["abc college","engineering","placements","ai lab"], JSON_UNESCAPED_UNICODE),
      ':SEARCH_TEXT'=>($basic['name'].' '.$basic['short_description'].' '.implode(' ', $basic['labs_summary'])),
      ':INFO_ADDED_BY'=>'system_admin',
      ':DATA_VERSION'=>1,
      ':DATA_STATUS'=>'PUBLISHED',
      ':SOURCE'=>'seed_script'
    ]);

    // COURSES row
    $ins->execute([
      ':DATAID'=>uuid_v4_simple(),
      ':CLG_ID'=>$clgId,
      ':DATA_TYPE'=>'COURSES',
      ':CLG_BASIC'=>null,
      ':CLG_LOCATIONS'=>null,
      ':CLG_COURSES'=>json_encode($courses, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
      ':CLG_FEES'=>null,
      ':CLG_DEPARTMENTS'=>null,
      ':KEYWORDS'=>json_encode(["be computer","me","diploma","courses"], JSON_UNESCAPED_UNICODE),
      ':SEARCH_TEXT'=>'B.E. Computer Engineering B.E. Electronics M.E. programs Diploma courses',
      ':INFO_ADDED_BY'=>'system_admin',
      ':DATA_VERSION'=>1,
      ':DATA_STATUS'=>'PUBLISHED',
      ':SOURCE'=>'seed_script'
    ]);

    // DEPARTMENTS row
    $ins->execute([
      ':DATAID'=>uuid_v4_simple(),
      ':CLG_ID'=>$clgId,
      ':DATA_TYPE'=>'DEPARTMENTS',
      ':CLG_BASIC'=>null,
      ':CLG_LOCATIONS'=>null,
      ':CLG_COURSES'=>null,
      ':CLG_FEES'=>null,
      ':CLG_DEPARTMENTS'=>json_encode($departments, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
      ':KEYWORDS'=>json_encode(["departments","hod","faculty"], JSON_UNESCAPED_UNICODE),
      ':SEARCH_TEXT'=>'Computer Electronics Mechanical Civil HOD labs research',
      ':INFO_ADDED_BY'=>'system_admin',
      ':DATA_VERSION'=>1,
      ':DATA_STATUS'=>'PUBLISHED',
      ':SOURCE'=>'seed_script'
    ]);

    // OTHER row (placements & infra)
    $ins->execute([
      ':DATAID'=>uuid_v4_simple(),
      ':CLG_ID'=>$clgId,
      ':DATA_TYPE'=>'OTHER',
      ':CLG_BASIC'=>json_encode($other, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
      ':CLG_LOCATIONS'=>null,
      ':CLG_COURSES'=>null,
      ':CLG_FEES'=>null,
      ':CLG_DEPARTMENTS'=>null,
      ':KEYWORDS'=>json_encode(["placements","incubation","hostel","library"], JSON_UNESCAPED_UNICODE),
      ':SEARCH_TEXT'=>'placements incubation hostel research ABCTech MOUs',
      ':INFO_ADDED_BY'=>'system_admin',
      ':DATA_VERSION'=>1,
      ':DATA_STATUS'=>'PUBLISHED',
      ':SOURCE'=>'seed_script'
    ]);

    // insert QA suggestions
    $qaIns = $pdo->prepare("INSERT INTO college_qa_suggestions (ID, CLG_ID, QUESTION, ANSWER, TAGS, IS_ACTIVE, SOURCE, RANK_SCORE, ADDED_ON) VALUES (:ID,:CLG_ID,:QUESTION,:ANSWER,:TAGS,1,:SOURCE,:RANK_SCORE,NOW())");
    $qas = [
      ["What scholarships are available at ABC College?","ABC College offers merit scholarships (up to 50% tuition) for top performers, need-based assistance for eligible students, and company-sponsored fellowships.","[\"scholarships\",\"financial aid\",\"merit\"]", 0.8],
      ["Does ABC College offer internships with industry partners?","Yes — active internship pipelines via MOUs; the placement cell maintains an internship board and supports 6–12 week internships.","[\"internship\",\"mou\",\"industry\"]",0.9],
      ["Can I apply for the Incubation Centre?","Yes — students with a prototype and faculty endorsement can apply; selected teams get mentorship and seed access.","[\"incubation\",\"startup\",\"entrepreneurship\"]",0.75],
      ["How do I pay fees?","Fees can be paid online (Netbanking/UPI/Cards), by demand draft, or EMI where supported by partner banks.","[\"fees\",\"payment\",\"emi\"]",0.6],
      ["Is hostel available?","Yes — separate hostel for boys/girls; apply early. Hostel fees approx ₹40,000/yr with limited seats.","[\"hostel\",\"accommodation\",\"fees\"]",0.65],
      ["Who are the top recruiters?","Top recruiters include TechCorp, BuildIt, SoftServe, FinData and GreenEnergy. Placement drives happen annually.","[\"placements\",\"recruiters\",\"companies\"]",0.85]
    ];
    foreach ($qas as $q) {
      $qaIns->execute([
        ':ID'=>uuid_v4_simple(),
        ':CLG_ID'=>$clgId,
        ':QUESTION'=>$q[0],
        ':ANSWER'=>$q[1],
        ':TAGS'=>$q[2],
        ':SOURCE'=>'seed_script',
        ':RANK_SCORE'=>$q[3]
      ]);
    }

    // commit
    $pdo->commit();

    // Optional: set colleges row to APPROVED if exists
    $u = $pdo->prepare("UPDATE colleges SET CLG_DATA_STATUS='APPROVED', CLG_UPDATED_ON=NOW() WHERE CLGID = :clg");
    $u->execute([':clg'=>$clgId]);

    echo "Seeding completed for ABC College (CLG_ID = $clgId)\n";

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo "Error during seeding: " . $e->getMessage() . "\n";
    exit(1);
}
