-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 25, 2025 at 12:15 AM
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
-- Database: `fretnotes`
--

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `thread_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `author` varchar(100) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `edited_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `thread_id`, `content`, `created_at`, `author`, `date`, `edited_at`) VALUES
(4, 1, 'anjayyy busettt', '2025-11-10 13:22:36', 'vito', '2025-11-10 06:22:36', NULL),
(9, 9, 'betul', '2025-11-17 13:14:52', 'radityadwiki', '2025-11-17 06:14:52', NULL),
(12, 10, 'vito', '2025-11-17 13:26:54', 'radityadwiki', '2025-11-17 06:26:54', NULL),
(13, 10, 'zacky', '2025-11-17 13:27:00', 'radityadwiki', '2025-11-17 06:27:00', NULL),
(14, 10, 'rama', '2025-11-17 13:27:09', 'radityadwiki', '2025-11-17 06:27:09', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `user_id` int(11) NOT NULL,
  `song_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`user_id`, `song_id`) VALUES
(10, 8),
(11, 3),
(11, 8);

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `user_id` int(11) NOT NULL,
  `song_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `songs`
--

CREATE TABLE `songs` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `artist` varchar(255) NOT NULL,
  `genre` varchar(100) DEFAULT NULL,
  `version_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `song_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `chords_text` text DEFAULT NULL,
  `tab_text` text DEFAULT NULL,
  `created_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `songs`
--

INSERT INTO `songs` (`id`, `title`, `artist`, `genre`, `version_name`, `created_at`, `song_status`, `chords_text`, `tab_text`, `created_by`) VALUES
(3, '3 AM', 'Gregory Alan Isakov', 'Folk', 'Original', '2025-10-12 08:09:17', 'Approved', '[Intro]\r\nC2       G2    Am     Am     F1      F1      G1      G2\r\nC2      G2      Am    Am     F2     C2     G1      C2\r\nG1      G2      G3     G4\r\n \r\n[Verse]\r\nC              G3                Am         Am7/G          F\r\nWell, it\'s 3 a.m. again, like it always seems to be\r\nG1                  G2                           Am         Am/G      F\r\nDriving northbound, driving homeward, driving wind is driving me.\r\nG1                G2             Am    Am7/G      F\r\nIt just seems so funny how I always end up here\r\nG1                 G2                      Am      Am/G       F\r\nwalking outside in a storm while looking way up past the treeline.\r\n        G1      G2      G3     G4\r\nIt\'s been some time.\r\n \r\n[Chorus]\r\n        Am              F               G5                    G5\r\nGive me darkness when I\'m dreaming, give me moonlight when I\'m leaving,\r\nAm                      F               G5            G5\r\nGive me shoes that weren\'t made for standing.\r\n Am                     F               G5             G5\r\nGive me treeline, give me big sky, give me snowbound, give me rainclouds,\r\n  Am                     G1     G2     G3     G4\r\ngive me bedtime just sometimes.\r\n \r\n[Verse]\r\n                C          G3           Am   Am7/G    F\r\nNow you\'re talking in my room, there ain\'t nobody here,\r\n                  G1               G2               Am        Am/G        F\r\nCause I\'ve been driving like a trucker, I\'ve been wearing through the gears\r\n            G1                 G2               Am    Am/G        F\r\nI\'ve been training like a soldier, I\'ve been burning through this sorrow\r\n       G1            G2 Am      Am/G   F     G1      G2     G3     G4\r\nThe only talking lately is a background radio\r\nDm            F               G5              G5\r\nYou are my friend and I was a saint\r\nDm            F                                G5             G5\r\nAnd riding that hope was like catching some train\r\nDm            F                                G5             G5\r\nNow I just walk, but I don\'t mind the rain\r\nDm       F                                      G5             G5\r\nSinging so much softer than I did back then\r\n \r\n[Interlude]\r\nC2      G2     Am     Am     F2     C2     G1      C2\r\nG1       G2     G3     G4\r\n \r\n[Verse]\r\n        C                       G      Am       Am7/G   F\r\nWell the night I think is darker, than we can really say,\r\n               G1            G2          Am     Am/G      F\r\nGod\'s been living in that ocean, sending us all the big waves\r\n        Am              F              G5                     G5\r\nAnd I wish I was a sailor so I could know just how to trust\r\nPage 2/3\r\n        Am                  F               G5                        G5\r\nMaybe I could bring some grace back home to dry land for each of us\r\nDm         F                   G5             G5\r\nSay what you see, you say it so well\r\nDm                  F                   G5          G5\r\nJust say you will wait like snow on the rail\r\nDm            F                                G5             G5\r\nCombing that train yard for some kind of sign\r\nDm            F                        G5             G5\r\nEven my own self, it just don\'t seem mine\r\n \r\n[Chorus]\r\n        Am                      F           G5                 G5\r\nGive me darkness when I\'m dreaming, give me moonlight when I\'m leaving\r\n        Am                      F          G5                 G5\r\nGive me mustang horse and muscle, oh, I won\'t be going gentle\r\n        Am                           F               G5                 G5\r\nGive me slandered looks when I\'m lying, give me fingers when I\'m crying\r\n    Am                 F               G5                 G5\r\nI ain\'t out there to cheat you, see I killed that damn coyote in me\r\n \r\n[Outro]\r\nAm                 Am                  F                      F\r\nG5                 G5                  G5                     G5', 'Capo 2\r\n \r\ne|----------3----1----------1-------------------------------------------------|\r\nB|--------1---1--------------------0/1----------1-----------------1-----------|\r\nG|------0------------0---------------------0/2------2--------0/2-----2--------|\r\nD|----2------------0t---------0t--------2t--------2t------2t------------------|\r\nA|--3t------------------------------0t-------0t---------0t-----0t-------------|\r\nE|---------------3t------3t---------------------------------------------------|\r\n \r\n     C2          G2                  Am\r\n \r\ne|-----------------------------1—0--------------------------------------------|\r\nB|--1-----------1---------------------1---1-----1/3-----1---------------------|\r\nG|--------0/2---------2----------------------------------------------0--------|\r\nD|-----3t---------3t-------3t-----3t----------2t----------0t-------0t---------|\r\nA|------------------------------------------3t--------------------------------|\r\nE|--1t------1t----------1t-----1t--------------------3t--------3t-------3t----|\r\n \r\n     F1                                     C1       G3\r\n \r\n[Chords]\r\ne|--0---0---0-------0--------3---1---0---0------5---3----1--------------|\r\nB|--1---1---1---1---1--------1---0---3---1------0---1----3--------------|\r\nG|--0---0---2---0---2--------0---0---0---0------0---0----2--------------|\r\nD|--2---0---2---2---3--------0---0---0---0(2)---3---2----0--------------|\r\nA|--3---2---0---x---3--------2---2---2---2----------3-------------------|\r\nE|------3-------3---1--------3---3---3---3------------------------------|', 4),
(6, 'Membasuh', 'Hindia', 'Pop', 'Original', '2025-10-12 09:50:05', 'Approved', NULL, NULL, 4),
(7, 'Evakuasi', 'Hindia', 'Pop-Rock', 'Live Version', '2025-10-19 12:41:48', 'Approved', NULL, NULL, 4),
(8, 'Dialek Dini Hari', 'Jason Ranti', 'Folk', 'Original', '2025-10-19 15:52:17', 'Approved', NULL, NULL, 4),
(13, 'Youth', 'Vitto\'', 'Pop', 'Live Version', '2025-11-18 04:06:31', 'Approved', NULL, NULL, 4),
(14, 'punkkin', 'zackyguanteng', 'R&B', 'copplo', '2025-11-19 06:41:01', 'Approved', NULL, NULL, 4),
(17, 'COKLATHITAM', 'HAXXI', 'Rap', 'Original', '2025-11-21 02:13:44', 'Approved', 'Intro:\r\nG D Em C\r\nG D Em C\r\n\r\nVerse:\r\nG D\r\nKulepas semua\r\nEm C\r\nYang kuinginkan\r\nG D\r\nTak akan ku ulangi\r\nEm C\r\nMaafkan kalau\r\n\r\nChorus:\r\nG D\r\nAku jatuh cinta\r\nEm C\r\nCinta pada mu\r\nG D\r\nAku jatuh cinta\r\nEm C\r\nCinta pada mu\r\n', '-', 11),
(20, 'Variasi Pink', 'Jason Ranti', 'Folk - Pop', 'Live Version', '2025-11-21 15:10:22', 'Approved', '[intro]\r\nD A D A\r\n \r\nD\r\nTerjadi lagi malaikatku\r\n             A\r\nterlambat datang\r\nD\r\nKebanyakan dandan\r\n               A\r\nwajahnya mustahil telanjang\r\n \r\nD\r\nBerjam-jam didepan kaca\r\n         A\r\namati dimuka\r\nD\r\nIa yakin penting\r\n                A\r\nbibirnya rasa strowberry ~\r\n         D\r\nSungguh tak penting, aku tak ingin\r\n        A\r\nrasa strowberry, lipstick warna pink\r\n         D\r\nSungguh tak penting, aku tak ingin\r\n      A\r\nyang aku ingin, ia telanjang (tok)\r\nD A D A\r\nEhmmmm……Ehmm…… ~\r\nD A D A\r\naaa ~ aa ~\r\nD\r\n Masalah lipstick malaikatku\r\n               A\r\nobsesif kompulsif\r\nD\r\nTiga lapis warna\r\n              A\r\nbagaikan melukis di-kanvas\r\nD\r\nPink, pink, pink kombinasi pink\r\n         A\r\nvariasi pink\r\nD                            A\r\nPink, pink, pink, pink pink pink ~\r\n        D\r\nSungguh tak penting, aku tak ingin\r\n      A\r\nsiapa peduli, lipstick warna pink\r\n         D\r\nSungguh tak penting, aku tak ingin\r\n      A\r\nyang aku ingin ia telanjang\r\nD    A     D      A\r\nem ~ hem ~ yehh…hemmm…\r\nD\r\nPink, pink, pink kombinasi pink\r\n         A\r\nvariasi pink\r\nD                            A\r\nPink, pink, pink, pink pink pink ~\r\n    D\r\nOh, Pink, pink, pink, pink pink pink\r\n             A\r\nPink, pink, pink ~\r\n D                           A\r\nPink, pink, pink, pink pink pink ~\r\n \r\n[Outro]\r\nD', '-', 11),
(21, 'Kita', 'Sheila On 7', 'Pop', 'Original', '2025-11-22 15:12:39', 'Approved', '[Intro]\r\nC  Em7  Dm  G\r\nC  Em7  Dm  G\r\n \r\n[Verse 1]\r\nC                    E7                     Dm         G          C       F - G\r\nDisaat kita bersama, diwaktu kita tertawa menangis merenung, oleh cinta\r\nC                       E7                            Dm       G              C\r\nKau coba hapuskan rasa, rasa dimana kau melayang jauh dari jiwaku, juga mimpiku\r\nDm                   C\r\nBiarlah biarlah, hariku dan harimu\r\nEm       Bb      G\r\nTerbelenggu satu oleh ucapan manismu\r\n \r\n[Chorus]\r\n             F               C\r\nDan kau bisikkan kata cinta\r\n                Dm        Am\r\nKau telah percikkan, rasa sayang\r\n            F    C\r\nPastikan kita seirama\r\n          Dm        Am\r\nWalau terikat, rasa hina\r\n \r\n[Interlude]\r\nC  Em7  Dm  G\r\n \r\n[Verse 2]\r\nC                        E7                        Dm           G\r\nSekilas kau tampak layu, jika kau rindukan gelak tawa yang warnai\r\n               C\r\nlembar jalan kita\r\nDm                       C\r\nReguk dan reguklah, mimpiku dan mimpimu\r\nEm       Bb       G\r\nTerbelenggu satu, oleh ucapan janjimu\r\n \r\n[Chorus]\r\n             F        C\r\nDan kau bisikkan kata cinta\r\n                Dm        Am\r\nKau telah percikkan, rasa sayang\r\n           F     C\r\nPastikan kita seirama\r\n          Dm        Am\r\nWalau terikat, rasa hina\r\n \r\n[Solo]\r\nF  C  Dm  Am\r\nF  C  Dm  Am\r\n \r\n[Chorus]\r\n             F        C\r\nDan kau bisikkan kata cinta\r\n                Dm        Am\r\nKau telah percikkan, rasa sayang\r\n           F     C\r\nPastikan kita seirama\r\n           Dm       Am\r\nWalau terikat, rasa hina\r\n \r\n[Outro]\r\n             F        C\r\nDan kau bisikkan kata cinta\r\n                Dm        Am\r\nKau telah percikkan, rasa sayang\r\n          F     C\r\nAkankah kita seirama\r\n         Dm        Am      Am     F\r\nSaat terikat, rasa hina.', 'e|----------------------------------|-------------------------------------|\r\nB|----------------------------------|-------------------------------------|\r\nG|--10--10-s-9---9-s-7---7-s-5-s--9-|--12--12-s-10--10-s-9--9-s-7--7-s-12-|\r\nD|--10--10-s-9---9-s-7---7-s-5-s--9-|--12--12-s-10--10-s-9--9-s-7--7-s-12-| x2\r\nA|---8---8-s-7---7-s-5---5-s-3-s--7-|--10--10-s--8---8-s-7--7-s-5--5-s-10-|\r\nE|----------------------------------|-------------------------------------|', 11),
(22, 'Dan', 'Sheila On 7', 'Pop', 'Original', '2025-11-22 15:16:06', 'Approved', 'D           C#m             Bm\r\nDan, bila esok datang kembali\r\n            A\r\nseperti sejak mula dimana kau bisa bercanda\r\nD                 C#m        Bm\r\ndan perlahan kau pun lupakan aku\r\n            A                                   D\r\nmimpi burukmu dimana t\'lah kutancapkan duri tajam\r\n             C#m            Bm  E           F#m      E\r\nkau pun menangis, menangis sedih, maafkan aku\r\n \r\nD                 C#m           Bm        A\r\nDan, bukan maksudku, bukan inginku melukaimu\r\n                                  D\r\nSadarkah kau di sini ku pun terluka\r\n          C#m       Bm   E          F#m       E\r\nmelupakanmu, menepikanmu, maafkan aku\r\n \r\n \r\n[Chorus]\r\n                             A\r\nLupakanlah / Caci maki saja diriku\r\n              C#m\r\nbila itu bisa membuatmu\r\n                  Bm\r\nkembali bersinar dan berpijar\r\n           G      E\r\nseperti dulu kala\r\n                A\r\nCaci maki saja diriku\r\n                C#m\r\nbila itu bisa membuatmu\r\n                   Bm\r\nkembali bersinar dan berpijar\r\n           G       E\r\nseperti dulu kala', ' \r\nEb|-------2----3-----3-2-----|\r\nBb|-----3---3----3-------3---|\r\nGb|---2------------2---------|\r\nDb|-0------------------------|\r\nAb|--------------------------|\r\nEb|--------------------------|(x4)', 13),
(23, 'Coastline', 'Hollow Coves', 'Pop - Folk', 'Live Version', '2025-11-24 00:35:00', 'Pending', '[Intro]\r\nG    Bm  A  x2\r\n \r\n[Verse 1]\r\nG\r\n I\'m leaving home for the Coastline\r\nBm                    A\r\n Some place under the sun\r\nG\r\n I feel my heart for the first time\r\nBm                     A                   G    A\r\n \'Cause now I\'m moving on yeah, I\'m moving on\r\n \r\nG\r\n And there\'s a place that I\'ve dreamed of\r\nBm                   A\r\n Where I can free my mind\r\nG\r\n I hear the sounds of the season\r\n           Bm         A\r\n And lose all, sense of time\r\n \r\n[Chorus]\r\nG\r\n I\'m moving far away\r\nBm\r\n To a sunny place\r\nG\r\n Where it\'s just you and me\r\nBm                         A\r\n Feels like we\'re in a dream\r\n     D           G     A\r\n You know what I mean\r\n \r\n[Verse 2]\r\nG\r\n The summer air by the seaside\r\nBm                     A\r\n The way it fills our lungs\r\nG\r\n The fire burns in the night sky\r\nBm                      A                   G      A\r\n This life will keep us young yeah, keep us young\r\n \r\nG\r\n And we will sleep by the ocean\r\nBm                              A\r\n Our hearts will move with the tide\r\nG\r\n And we will wake in the morning\r\n            Bm           A\r\n To see the sun, paint the sky\r\n \r\n[Chorus]\r\nG\r\n I\'m moving far away\r\nBm\r\n To a sunny place\r\nG\r\n Where it\'s just you and me\r\nBm                         A\r\n Feels like we\'re in a dream\r\n                 G\r\n You know what I mean\r\n \r\n[Instrumental]\r\n(G)   Bm  A  G    Bm  A\r\n \r\n[Chorus]\r\nG\r\n I\'m moving far away\r\nBm             A\r\n To a sunny place\r\nG\r\n Where it\'s just you and me\r\nBm                         A\r\n Feels like we\'re in a dream\r\n     D    A      G\r\n You know what I mean\r\n \r\n(G)\r\n I\'m moving far away\r\nBm\r\n To a sunny place\r\nG\r\n Where it\'s just you and me\r\nBm                         A\r\n Feels like we\'re in a dream\r\n     D           G\r\n You know what I mean', '-', 11);

-- --------------------------------------------------------

--
-- Table structure for table `threads`
--

CREATE TABLE `threads` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `author` varchar(100) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `content_hash` char(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `threads`
--

INSERT INTO `threads` (`id`, `title`, `content`, `image_path`, `author`, `date`, `content_hash`) VALUES
(1, 'Forum Pertama', 'Website FretNotes untuk tugas akhir pemrograman web', 'uploads/threads/guitar4.jpg', 'Shanum Naysila Priyambodo', '2025-11-03 07:37:41', '7604ff0200976c6a103cb3271b75d519'),
(6, 'Efek Rumah Kaca Band', 'Efek Rumah Kaca (sering disingkat ERK) adalah sebuah grup musik indie asal Jakarta, Indonesia. Grup ini dikenal karena lirik-liriknya yang puitis, cerdas, serta sarat dengan kritik sosial-politik.\r\nFormasi grup ini saat ini adalah Cholil Mahmud (vokal, gitar), Akbar Bagus Sudibyo (drum), Cempaka Asriani (bass), dan Gracia Violy (gitar).[2] Sampai sekarang, mereka telah merilis empat album studio, satu album mini, dan beberapa singel.', 'uploads/threads/efekrumahkaca.jpg', 'rezareza', '2025-11-17 03:46:48', '379e0cc530e5de73d931dde791908e9d'),
(7, 'SORE Band', 'Sore atau Sore Ze Band merupakan kelompok musik indie yang berasal dari Jakarta. Sore memiliki keunikan yaitu semua anggotanya bermain musik dengan kidal.\r\nSampai saat ini Sore telah menghasilkan lima album studio dan beberapa kompilasi. Anggota Sore antara lain adalah Ade Firza Paloh (gitar, vokal), Awan Garnida (bass, vokal), Reza Dwiputranto (gitar, vokal), Bemby Gusti (drum, perkusi, vokal), Ramondo Gascaro (piano, keyboard, gitar, vokal). Semua anggota Sore ambil bagian sebagai vokalis dalam setiap album-albumnya.', 'uploads/threads/SORE.jpg', 'radityadwiki', '2025-11-17 03:57:50', 'c30993b36acb745db2d79e90004997af'),
(8, 'Duara', 'Duara merupakan duo musik asal Jakarta yang terdiri dari Robert Mulyarahardja dan Renita Martadinata. Duara mengusung filosofi pop-agnostic, yaitu perpaduan antara unsur-unsur musik pop dengan elemen-elemen berbagai macam genre lain yang dirangkai secara harmonis untuk menyajikan imajinasi yang luas bagi para pendengarnya.', 'uploads/threads/duara.jpg', 'Shanum Naysila Priyambodo', '2025-11-17 04:01:38', '663a97315ca05443e9b3331122f16efa'),
(9, 'Soenji Musik', 'Soenji (Sunyi) adalah grup musik yang terbentuk oleh dua personel yaitu Afif Abdulloh dan Febryan Tricahyo. Musik mereka hadir dengan nuansa kontemplatif, mencerminkan kisah dan pengalaman pribadi yang dekat dengan kehidupan sehari - hari, seperti tentang keluarga, lingkungan, dan perjalanan hidup mereka. Dengan gaya sederhana khas musik jawa barat', 'uploads/threads/soenji.jpeg', 'Shanum Naysila Priyambodo', '2025-11-17 04:13:25', '0baf992df9e420313942ecd7a317deb6'),
(10, 'Jason Ranti', 'Patrick Jason Ranti (lahir 22 Oktober 1984) adalah seorang penyanyi, penulis lagu, dan pelukis asal Indonesia. Ia mulai dikenal atas album debut folk-nya, Akibat Pergaulan Blues (2017). Ia juga dikenal luas atas lagu &quot;Lagunya Begini Nadanya Begitu&quot; yang ia ciptakan untuk penyair Sapardi Djoko Damono.', 'uploads/threads/jasonranti.jpeg', 'radityadwiki', '2025-11-17 04:18:38', 'f5ed6fb024ef275ba3f2f9cc75827cd3');

-- --------------------------------------------------------

--
-- Table structure for table `thread_emotes`
--

CREATE TABLE `thread_emotes` (
  `id` int(11) NOT NULL,
  `thread_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `emote_type` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `thread_emotes`
--

INSERT INTO `thread_emotes` (`id`, `thread_id`, `user_id`, `emote_type`) VALUES
(12, 7, 11, 'love'),
(13, 10, 11, 'love'),
(14, 9, 13, 'happy'),
(16, 9, 11, 'happy');

-- --------------------------------------------------------

--
-- Table structure for table `thread_likes`
--

CREATE TABLE `thread_likes` (
  `id` int(11) NOT NULL,
  `thread_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `thread_likes`
--

INSERT INTO `thread_likes` (`id`, `thread_id`, `user_id`) VALUES
(35, 1, 10),
(37, 6, 11),
(38, 10, 10),
(39, 9, 11),
(40, 10, 13);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('user','admin') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `created_at`, `role`) VALUES
(3, 'raditya', 'rezaradit03@gmail.com', '$2y$10$QZ2vSKT1VbrV4EWEInCjVuh7s1iMvAb3WtbbVowfqS51oHh5nbJS6', '2025-10-12 07:03:22', 'user'),
(4, 'adminbesar', 'admin@gmail.com', '$2y$10$3ydsVNDG9RqJAhwTvgiW7.lBUvxwIhd9r6QkX36RXXReFUKzO8taS', '2025-10-12 07:18:58', 'admin'),
(6, 'Past Someone', 'pastsomeone1@gmail.com', '$2y$10$9WDNd9cJGp68DAqqcKOMQOMiOG2.go0Ps2U5uBgiQZ1/GdzGGDxfW', '2025-10-14 13:30:24', 'user'),
(10, 'vito', 'vitoj@gmail.com', '$2y$10$mDztUYwr81e2Nph9fNdMdO33dxZnHpUuReuVqUoJqR/.hXiK.uPT6', '2025-10-27 12:58:10', 'user'),
(11, 'radityadwiki', 'raditya01052007@gmail.com', '$2y$10$KusaQ1Dxjl74q0HAm6Lvj..7Up5xp90.Q6.He57rHdjhJuJXYAt4a', '2025-11-14 01:04:00', 'user'),
(13, 'zackygancet', 'zackyguy1@gmail.com', '$2y$10$Xf8w785vmBPTlMrTfhMjse85WGwISdEoCg9BYghnT/iI8uMN.8KMu', '2025-11-19 06:42:53', 'user'),
(16, 'WyzarD', 'wyzard57@gmail.com', '$2y$10$K2h6icWyTcUw3iLUqWUby.r/TYb33IYtYQ/6jlwDG/fc39IpNcQDq', '2025-11-19 13:48:07', 'user'),
(17, 'gatau', 'gataugatau@gmail.com', '$2y$10$WGKY30Wd2FQZuekSvgV9qOTBy7Wtr6f1xeUTv11FCBRMNM5dqm6Q6', '2025-11-21 05:56:03', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `thread_id` (`thread_id`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`user_id`,`song_id`),
  ADD KEY `song_id` (`song_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`user_id`,`song_id`),
  ADD KEY `song_id` (`song_id`);

--
-- Indexes for table `songs`
--
ALTER TABLE `songs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `threads`
--
ALTER TABLE `threads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_thread_hash` (`content_hash`);

--
-- Indexes for table `thread_emotes`
--
ALTER TABLE `thread_emotes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `thread_id` (`thread_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `thread_likes`
--
ALTER TABLE `thread_likes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `thread_id` (`thread_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `songs`
--
ALTER TABLE `songs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `threads`
--
ALTER TABLE `threads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `thread_emotes`
--
ALTER TABLE `thread_emotes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `thread_likes`
--
ALTER TABLE `thread_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`song_id`) REFERENCES `songs` (`id`);

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`song_id`) REFERENCES `songs` (`id`);

--
-- Constraints for table `thread_emotes`
--
ALTER TABLE `thread_emotes`
  ADD CONSTRAINT `thread_emotes_ibfk_1` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`),
  ADD CONSTRAINT `thread_emotes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `thread_likes`
--
ALTER TABLE `thread_likes`
  ADD CONSTRAINT `thread_likes_ibfk_1` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`),
  ADD CONSTRAINT `thread_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
