USE praktikum_web_2401020034;

INSERT INTO program_studi (nama_prodi) VALUES
('Teknik Informatika'),
('Sistem Informasi');

INSERT INTO mahasiswa
(nim, nama, email, usia, program_studi_id)
VALUES
('2401020001', 'Rizky Ramadhan', 'rizky@gmail.com', 20, 1),
('2401020002', 'Nabila Putri', 'nabila@gmail.com', 21, 1),
('2401020003', 'Fajar Maulana', 'fajar@gmail.com', 20, 2),
('2401020099', 'Data Sementara', 'sementara@gmail.com', 19, 2);