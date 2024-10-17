<?php
session_start();       // เริ่มต้น session
session_unset();       // เคลียร์ตัวแปรทั้งหมดใน session
session_destroy();     // ทำลาย session
header('Location: ./login.php');  // เปลี่ยนเส้นทางไปที่หน้า login.php
exit();                // หยุดการทำงานของสคริปต์
?>
