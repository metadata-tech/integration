-- Jalankan pada pangkalan data MariaDB sasaran sebelum Navicat Data Transfer.
-- Skrip ini sengaja tidak menggugurkan jadual yang mungkin sudah berisi.
CREATE TABLE `padanan_institusi_pengkhususan` (
  `kod` int NOT NULL AUTO_INCREMENT,
  `kod_institusi` varchar(10) DEFAULT NULL,
  `kod_pengkhususan` varchar(11) DEFAULT NULL,
  `id_peringkat_kelulusan` smallint DEFAULT NULL,
  `peringkat_kelulusan` int DEFAULT NULL,
  `kategori` int DEFAULT NULL COMMENT '1 = ikhtisas; 2 = bukan ikhtisas',
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `kod_mqa` varchar(100) DEFAULT NULL,
  `tarikh_cipta` datetime NOT NULL,
  `tarikh_ubahsuai` datetime DEFAULT NULL,
  `id_pencipta` int DEFAULT NULL,
  `id_pengubahsuai` int DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `mqr_kod_mqa_aktif` varchar(100) GENERATED ALWAYS AS (
    CASE
      WHEN `is_deleted` = 0 AND `kod_mqa` IS NOT NULL AND TRIM(`kod_mqa`) <> ''
      THEN UPPER(TRIM(`kod_mqa`))
      ELSE NULL
    END
  ) VIRTUAL,
  PRIMARY KEY (`kod`),
  UNIQUE KEY `mqr_kod_mqa_aktif_unique` (`mqr_kod_mqa_aktif`),
  KEY `kod_pengkhususan` (`kod_pengkhususan`),
  KEY `kod_institusi` (`kod_institusi`),
  KEY `peringkat_kelulusan` (`peringkat_kelulusan`),
  KEY `id_peringkat_kelulusan` (`id_peringkat_kelulusan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
