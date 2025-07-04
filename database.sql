CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('mahasiswa','asisten') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `enrollments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL, -- ID mahasiswa yang mendaftar
  `course_id` INT(11) NOT NULL, -- ID praktikum yang didaftar
  `tanggal_daftar` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_course_unique` (`user_id`, `course_id`), -- Pastikan 1 mahasiswa hanya bisa daftar 1 praktikum sekali
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `modules` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `course_id` INT(11) NOT NULL,
  `nama_modul` VARCHAR(255) NOT NULL,
  `deskripsi` TEXT,
  `file_materi` VARCHAR(255), -- Path ke file materi (PDF/DOCX)
  `tenggat_laporan` DATETIME, -- Batas waktu pengumpulan laporan
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `submissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `modul_id` INT(11) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `waktu_pengumpulan` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `nilai` INT(11) DEFAULT NULL,
  `feedback` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_modul_unique` (`user_id`, `modul_id`), -- Setiap mahasiswa hanya bisa mengumpulkan 1 laporan per modul
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`modul_id`) REFERENCES `modules`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;