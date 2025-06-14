-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 08, 2025 at 03:38 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `job_listing`
--

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `company` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `salary` varchar(100) DEFAULT NULL,
  `job_type` enum('Full-time','Part-time','Contract','Internship') NOT NULL,
  `description` text NOT NULL,
  `requirements` text NOT NULL,
  `contact_email` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `title`, `company`, `location`, `salary`, `job_type`, `description`, `requirements`, `contact_email`, `created_at`, `updated_at`) VALUES
(1, 'Frontend Developer', 'Tech Solutions Inc', 'Jakarta', 'Rp 8.000.000 - 12.000.000', 'Full-time', 'Kami mencari Frontend Developer yang berpengalaman untuk bergabung dengan tim kami. Anda akan bertanggung jawab untuk mengembangkan antarmuka pengguna yang menarik dan responsif.', 'Minimal 2 tahun pengalaman dengan React/Vue.js, HTML, CSS, JavaScript. Familiar dengan Git dan responsive design.', 'hr@techsolutions.com', '2025-05-31 10:55:52', '2025-05-31 10:55:52'),
(2, 'Backend Developer', 'Digital Innovations', 'Bandung', 'Rp 10.000.000 - 15.000.000', 'Full-time', 'Posisi Backend Developer untuk mengembangkan dan memelihara sistem backend yang scalable dan secure.', 'Pengalaman dengan PHP/Python/Node.js, Database MySQL/PostgreSQL, RESTful API, minimal 3 tahun pengalaman.', 'careers@digitalinnovations.com', '2025-05-31 10:55:52', '2025-05-31 10:55:52'),
(3, 'UI/UX Designer', 'Creative Studio', 'Surabaya', 'Rp 6.000.000 - 9.000.000', 'Full-time', 'Mencari UI/UX Designer kreatif untuk merancang pengalaman pengguna yang luar biasa untuk produk digital kami.', 'Portfolio yang kuat, pengalaman dengan Figma/Sketch, pemahaman tentang user research dan usability testing.', 'design@creativestudio.com', '2025-05-31 10:55:52', '2025-05-31 10:55:52'),
(4, 'Marketing Intern', 'StartUp Hub', 'Jakarta', 'Rp 2.000.000 - 3.000.000', 'Internship', 'Program magang marketing untuk fresh graduate yang ingin belajar digital marketing dan growth hacking.', 'Fresh graduate atau mahasiswa semester akhir, passionate tentang digital marketing, familiar dengan social media.', 'internship@startuphub.com', '2025-05-31 10:55:52', '2025-05-31 10:55:52'),
(5, 'Social Media Specialist', 'Jackable Corp', 'Los Angeles', 'Rp 6.000.000 - 7.000.000', 'Full-time', 'lorem', 'lorem', 'Jackable@mail.corp.us', '2025-05-31 12:47:32', '2025-05-31 12:47:32'),
(6, 'UI/UX Designer', 'Appify', 'Los Angeles', 'Rp 4.000.000 - 6.000.000', 'Contract', 'Analisis data bisnis', 'Flutter/React Native', 'design@designify.com', '2025-05-31 06:33:58', '2025-05-31 06:33:58'),
(7, 'UI/UX Designer', 'Appify', 'Jakarta', 'Rp 6.000.000 - 10.000.000', 'Full-time', 'Magang marketing 3 bulan', 'Leadership dan Agile', 'sosmed@trendmedia.com', '2025-05-31 06:33:58', '2025-05-31 06:33:58'),
(8, 'Social Media Specialist', 'Designify', 'Los Angeles', 'Rp 3.000.000 - 7.000.000', 'Internship', 'Testing dan pelaporan bug', 'Figma/Sketch wajib', 'sosmed@trendmedia.com', '2025-05-31 06:33:58', '2025-05-31 06:33:58'),
(9, 'UI/UX Designer', 'Appify', 'Malang', 'Rp 5.000.000 - 8.000.000', 'Internship', 'Membangun antarmuka web', 'Pengalaman HTML/CSS/JS', 'test@bugsquashers.com', '2025-05-31 06:33:58', '2025-05-31 06:33:58'),
(10, 'Frontend Developer', 'Tech Solutions Inc', 'Jakarta', 'Rp 8.000.000 - 12.000.000', 'Full-time', 'Kami mencari Frontend Developer yang berpengalaman...', 'Minimal 2 tahun pengalaman dengan React/Vue.js, HTML/CSS...', 'hr@techsolutions.com', '2025-05-31 10:55:52', '2025-05-31 10:55:52'),
(11, 'Backend Developer', 'Digital Innovations', 'Bandung', 'Rp 10.000.000 - 15.000.000', 'Full-time', 'Posisi Backend Developer untuk mengembangkan dan memelihara...', 'Pengalaman dengan PHP/Python/Node.js, Database MySQL/PostgreSQL...', 'careers@digitalinnovations.com', '2025-05-31 10:55:52', '2025-05-31 10:55:52'),
(12, 'UI/UX Designer', 'Creative Studio', 'Surabaya', 'Rp 6.000.000 - 9.000.000', 'Full-time', 'Mencari UI/UX Designer kreatif untuk merancang pengalaman pengguna...', 'Portfolio yang kuat, pengalaman dengan Figma/Sketch...', 'design@creativestudio.com', '2025-05-31 10:55:52', '2025-05-31 10:55:52'),
(13, 'Marketing Intern', 'StartUp Hub', 'Jakarta', 'Rp 2.000.000 - 3.000.000', 'Internship', 'Program magang marketing untuk fresh graduate atau...', 'Fresh graduate atau mahasiswa semester akhir, passionate...', 'internship@startuphub.com', '2025-05-31 10:55:52', '2025-05-31 10:55:52'),
(14, 'Data Scientist', 'Big Data Analytics', 'Jakarta', 'Rp 12.000.000 - 18.000.000', 'Full-time', 'Mencari Data Scientist untuk membangun model prediktif dan analisis data lanjutan.', 'Pengalaman dengan Python/R, SQL, machine learning frameworks. Minimal 1 tahun pengalaman di bidang data science.', 'recruitment@bigdata-analytics.com', '2025-05-31 13:47:13', '2025-05-31 13:47:13'),
(15, 'Mobile Developer', 'AppWorks Indonesia', 'Bandung', 'Rp 9.000.000 - 14.000.000', 'Full-time', 'Pengembangan aplikasi mobile cross-platform menggunakan Flutter/React Native.', 'Pengalaman dengan Flutter/React Native, Dart/JavaScript, dan integrasi API. Portfolio aplikasi sebelumnya diperlukan.', 'hr@appworks.id', '2025-05-31 13:47:13', '2025-05-31 13:47:13'),
(16, 'DevOps Engineer', 'Cloud Solutions', 'Jakarta', 'Rp 15.000.000 - 20.000.000', 'Contract', 'Implementasi CI/CD pipeline dan manajemen infrastruktur cloud.', 'Pengalaman dengan AWS/GCP, Docker, Kubernetes, Terraform. Sertifikasi cloud menjadi nilai tambah.', 'devops@cloudsolutions.co.id', '2025-05-31 13:47:13', '2025-05-31 13:47:13'),
(17, 'Content Writer', 'Media Kreatif', 'Yogyakarta', 'Rp 5.000.000 - 7.000.000', 'Part-time', 'Membuat konten artikel, blog, dan media sosial untuk berbagai klien.', 'Kemampuan menulis dalam Bahasa Indonesia dan Inggris yang baik. Pengalaman di bidang jurnalistik atau content marketing.', 'writer@mediakreatif.com', '2025-05-31 13:47:13', '2025-05-31 13:47:13'),
(18, 'HR Specialist', 'Talent Management', 'Jakarta', 'Rp 7.000.000 - 10.000.000', 'Full-time', 'Menangani rekrutmen, pengembangan karyawan, dan administrasi HR.', 'Minimal S1 Psikologi/Hukum/Manajemen, pengalaman 2 tahun di bidang HR. Menguasai UU Ketenagakerjaan.', 'hr@talentmgmt.com', '2025-05-31 13:47:13', '2025-05-31 13:47:13'),
(19, 'Graphic Designer', 'Digital Agency', 'Bali', 'Rp 6.500.000 - 8.500.000', '', 'Mendesain materi promosi digital untuk kampanye pemasaran.', 'Mahir Adobe Photoshop, Illustrator, After Effects. Kreatif dan memahami tren desain terkini.', 'creative@digitalagency-bali.com', '2025-05-31 13:47:13', '2025-05-31 13:47:13'),
(20, 'Sales Executive', 'Retail Solutions', 'Surabaya', 'Rp 4.500.000 + Komisi', 'Full-time', 'Menjual produk retail ke berbagai channel distribusi.', 'Kemampuan komunikasi dan negosiasi yang baik. Pengalaman sales retail minimal 1 tahun.', 'sales@retailsolutions.id', '2025-05-31 13:47:13', '2025-05-31 13:47:13'),
(21, 'Network Engineer', 'Telekom Indonesia', 'Jakarta', 'Rp 11.000.000 - 16.000.000', 'Full-time', 'Merancang dan memelihara infrastruktur jaringan perusahaan.', 'Sertifikasi CCNA/CCNP, pengalaman dengan routing, switching, dan firewall.', 'network@telekom-indonesia.com', '2025-05-31 13:47:13', '2025-05-31 13:47:13'),
(22, 'Customer Service', 'E-commerce Platform', 'Remote', 'Rp 4.000.000 - 5.500.000', 'Full-time', 'Menangani keluhan dan pertanyaan pelanggan via chat/email/telepon.', 'Kemampuan komunikasi yang baik, sabar, dan bisa bekerja shift.', 'cs@ecommerce-platform.id', '2025-05-31 13:47:13', '2025-05-31 13:47:13'),
(23, 'Product Manager', 'Tech Startup', 'Jakarta', 'Rp 18.000.000 - 25.000.000', 'Full-time', 'Memimpin pengembangan produk digital dari konsep hingga peluncuran.', 'Pengalaman 3+ tahun sebagai PM, memahami agile methodology. Background teknikal menjadi nilai tambah.', 'product@techstartup.id', '2025-05-31 13:47:13', '2025-05-31 13:47:13'),
(24, 'Parfume Tester', 'Mykonos', 'Jakarta', 'Rp 6.000.000 - 7.000.000', 'Full-time', 'lorem', 'lorem', 'mykonos@mail.com', '2025-06-07 18:37:46', '2025-06-07 18:37:46'),
(25, 'Parfume Tester', 'Mykonos', 'Jakarta', 'Rp 6.000.000 - 7.000.000', 'Full-time', 'lorem', 'lorem', 'mykonos@mail.com', '2025-06-07 18:42:05', '2025-06-07 18:42:05'),
(26, 'Parfume Tester', 'Mykonos', 'Jakarta', 'Rp 6.000.000 - 7.000.000', 'Full-time', 'lorem', 'lorem', 'mykonos@mail.com', '2025-06-07 18:42:09', '2025-06-07 18:42:09'),
(27, 'Parfume Tester', 'Mykonos', 'Jakarta', 'Rp 6.000.000 - 7.000.000', 'Full-time', 'lorem', 'lorem', 'mykonos@mail.com', '2025-06-07 18:42:10', '2025-06-07 18:42:10'),
(28, 'Table Maker', 'MableFirst', 'Makassar', 'Rp 4.000.000', 'Part-time', 'asdfgh', 'asdfgh', 'MF@gmail.com', '2025-06-07 18:43:34', '2025-06-07 18:43:34'),
(29, 'Table Maker', 'MableFirst', 'Makassar', 'Rp 4.000.000', 'Part-time', 'asdfgh', 'asdfgh', 'MF@gmail.com', '2025-06-07 18:49:44', '2025-06-07 18:49:44'),
(30, 'Table Maker', 'MableFirst', 'Makassar', 'Rp 4.000.000', 'Part-time', 'asdfgh', 'asdfgh', 'MF@gmail.com', '2025-06-07 18:49:49', '2025-06-07 18:49:49'),
(31, 'Operator Warnet', 'skyland', 'Yogyakarta', 'Rp 2.000.000', 'Part-time', 'a', 'a', 'skyland@biz.mail.com', '2025-06-07 18:50:45', '2025-06-07 18:50:45'),
(32, 'Operator Warnet', 'skyland', 'Yogyakarta', 'Rp 2.000.000', 'Part-time', 'a', 'a', 'skyland@biz.mail.com', '2025-06-07 18:50:48', '2025-06-07 18:50:48'),
(33, 'Operator Warnet', 'skyland', 'Yogyakarta', 'Rp 2.000.000', 'Part-time', 'a', 'a', 'skyland@biz.mail.com', '2025-06-07 18:51:32', '2025-06-07 18:51:32'),
(34, 'Operator Warnet', 'skyland', 'Yogyakarta', 'Rp 2.000.000', 'Part-time', 'a', 'a', 'skyland@biz.mail.com', '2025-06-07 18:51:36', '2025-06-07 18:51:36'),
(35, 'Operator Warnet', 'skyland', 'Yogyakarta', 'Rp 2.000.000', 'Part-time', 'a', 'a', 'skyland@biz.mail.com', '2025-06-07 18:51:39', '2025-06-07 18:51:39'),
(36, 'Operator Warnet', 'skyland', 'Yogyakarta', 'Rp 2.000.000', 'Part-time', 'a', 'a', 'skyland@biz.mail.com', '2025-06-07 18:52:05', '2025-06-07 18:52:05'),
(37, 'Operator Warnet', 'skyland', 'Yogyakarta', 'Rp 2.000.000', 'Part-time', 'a', 'a', 'skyland@biz.mail.com', '2025-06-07 18:52:07', '2025-06-07 18:52:07'),
(38, 'Operator Warnet', 'skyland', 'Yogyakarta', 'Rp 2.000.000', 'Part-time', 'a', 'a', 'skyland@biz.mail.com', '2025-06-07 18:52:22', '2025-06-07 18:52:22'),
(39, 'Operator Warnet', 'skyland', 'Yogyakarta', 'Rp 2.000.000', 'Part-time', 'a', 'a', 'skyland@biz.mail.com', '2025-06-07 18:52:24', '2025-06-07 18:52:24'),
(40, 'Operator Warnet', 'skyland', 'Yogyakarta', 'Rp 2.000.000', 'Part-time', 'a', 'a', 'skyland@biz.mail.com', '2025-06-07 18:52:26', '2025-06-07 18:52:26'),
(41, 'Operator Warnet', 'skyland', 'Yogyakarta', 'Rp 2.000.000', 'Part-time', 'a', 'a', 'skyland@biz.mail.com', '2025-06-07 18:52:39', '2025-06-07 18:52:39'),
(42, 'Operator Warnet', 'skyland', 'Yogyakarta', 'Rp 2.000.000', 'Part-time', 'a', 'a', 'skyland@biz.mail.com', '2025-06-07 18:52:55', '2025-06-07 18:52:55'),
(43, 'Hacker', 'Gacoan', 'Los Angeles', 'Rp 6.000.000 - 7.000.000', 'Part-time', 'a', 'a', 'gacoan@gmail.com', '2025-06-07 18:53:19', '2025-06-07 18:53:19'),
(44, 'Hacker', 'Gacoan', 'Los Angeles', 'Rp 6.000.000 - 7.000.000', 'Part-time', 'a', 'a', 'gacoan@gmail.com', '2025-06-07 18:53:21', '2025-06-07 18:53:21'),
(45, 'Vokalis Feast', 'FeastCorp', 'Jakarta', 'Rp 14.000.000', 'Full-time', 'a', 'a', 'feastcorp@mail.id', '2025-06-07 19:01:00', '2025-06-07 19:01:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
