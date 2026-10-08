<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Mahasiswa</title>
</head>
<body>

    <h1>Data Mahasiswa</h1>

    @if ($nim)
        <p>Filter NIM: <strong>{{ $nim }}</strong></p>
    @endif

    @if ($daftarMahasiswa)
        <table border="1" cellpadding="8" cellspacing="0">
            <thead>
                <tr>
                    <th>NIM</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Usia</th>
                    <th>Program Studi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($daftarMahasiswa as $mahasiswa)
                    <tr>
                        <td>{{ $mahasiswa['nim'] }}</td>
                        <td>{{ $mahasiswa['nama'] }}</td>
                        <td>{{ $mahasiswa['email'] }}</td>
                        <td>{{ $mahasiswa['usia'] }}</td>
                        <td>{{ $mahasiswa['nama_prodi'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>Data tidak ditemukan.</p>
    @endif

    @if ($nim)
        <p>
            <a href="{{ url('/mahasiswa') }}">Tampilkan semua data</a>
        </p>
    @endif

</body>
</html>