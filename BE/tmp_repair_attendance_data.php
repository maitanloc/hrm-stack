<?php
require_once __DIR__ . '/bootstrap.php';
use App\Core\Database;
$db = Database::connection();

echo "Repairing encoding/mojibake in attendances table...\n";

// Fix status column
$db->query("UPDATE attendances SET status = 'ĐÃ_DUYỆT' WHERE status LIKE '%\u0110\u00c3_DUY\u1ec6T%' OR status LIKE '%??_DUY?T%' OR status = 'ĐÃ_DUYỆT'");
$db->query("UPDATE attendances SET status = 'CHỜ_DUYỆT' WHERE status LIKE '%CH\u00e1\u00bb\u0153_DUY\u00e1\u00bb\u2020T%' OR status LIKE '%CH?_DUY?T%' OR status = 'CHỜ_DUYỆT'");
$db->query("UPDATE attendances SET status = 'TỪ_CHỐI' WHERE status LIKE '%T\u00e1\u00bb\u00a6_CH\u00e1\u00bb\u2018I%' OR status LIKE '%T?_CH?I%' OR status = 'TỪ_CHỐI'");

// Fix work_type column
$db->query("UPDATE attendances SET work_type = 'VĂN_PHÒNG' WHERE work_type LIKE '%V\u00c4\u201aN_PH\u00c3\u2019NG%' OR work_type LIKE '%V?N_PH?NG%' OR work_type = 'VĂN_PHÒNG'");

// Fix methods
$db->query("UPDATE attendances SET check_in_method = 'FACE_KIOSK' WHERE check_in_method = 'MÁY_QUÉT' AND notes LIKE '%Kiosk%'");
$db->query("UPDATE attendances SET check_out_method = 'FACE_KIOSK' WHERE check_out_method = 'MÁY_QUÉT' AND notes LIKE '%Kiosk%'");

echo "Done.\n";
