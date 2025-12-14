<?php

namespace App\Controllers;
use App\Models\DebtModel;
use App\Models\CicilanModel;

class DebtController extends BaseController
{
    public function index()
    {
        $model = new DebtModel();
        return $this->response->setJSON($model->getWithCicilan());
    }

    public function delete($id)
    {
        $model = new DebtModel();
        $cicilan = new CicilanModel();

        // Hapus cicilan dulu
        $cicilan->where('debt_id', $id)->delete();

        // Hapus debt
        $model->delete($id);

        // Redirect ke halaman mobile dengan flash message
        return redirect()->to('/debt/page')->with('success', 'Data hutang berhasil dihapus.');
    }

    // Tampilkan halaman desktop/mobile
    public function page()
    {
        $agent = $this->request->getUserAgent();
        $model = new DebtModel();
        $debts = $model->getWithCicilan();

        if($agent->isMobile()) {
            return view('debt/debts_mobile', ['debts' => $debts]);
        } else {
            return view('debt/debts_list', ['debts' => $debts]);
        }
    }



// Tampilkan form tambah hutang baru
public function add()
{
    $debtModel = new DebtModel();

    // Ambil nama aplikasi unik dari database
    $namaAplikasiList = $debtModel->getDistinctNamaAplikasi();

    // Kirim ke view
    return view('debt/debt_add', [
        'namaAplikasiList' => $namaAplikasiList
    ]);
}



	// Simpan data hutang baru
// Simpan data hutang baru + generate cicilan otomatis
public function save()
{
    $debtModel = new DebtModel();
    $cicilanModel = new CicilanModel();

    $namaAplikasi = $this->request->getPost('namaAplikasi');
    $tenor = (int) $this->request->getPost('tenor');
    $jatuhTempoPertama = $this->request->getPost('jatuhTempoPertama');
    $jumlahCicilan = (int) $this->request->getPost('jumlahCicilan');

    if (!$namaAplikasi || !$tenor || !$jatuhTempoPertama || !$jumlahCicilan) {
        return redirect()->back()->with('error', 'Data tidak lengkap');
    }

    // Hitung total pinjaman
    $jumlahPinjaman = $tenor * $jumlahCicilan;

    // 2. Insert ke table debts
    $debtId = $debtModel->insert([
        'namaAplikasi'   => $namaAplikasi,
        'jumlahPinjaman'=> $jumlahPinjaman,
        'jumlahTenor'   => $tenor
    ], true); // true = return insert ID

    // 2. Generate cicilan otomatis
    $tanggal = new \DateTime($jatuhTempoPertama);

    for ($i = 1; $i <= $tenor; $i++) {

        $cicilanModel->insert([
            'debt_id' => $debtId,
            'tanggal' => $tanggal->format('Y-m-d'),
            'jumlah'  => $jumlahCicilan
        ]);

        $tanggal->modify('+1 month');
    }

    return redirect()->to('/debt/page')
        ->with('success', 'Hutang dan cicilan berhasil ditambahkan.');
}



// Tampilkan form ubah hutang & tambah cicilan
public function edit($id)
{
    $debtModel = new DebtModel();
    $cicilanModel = new CicilanModel();

    $debt = $debtModel->find($id);
    $cicilan = $cicilanModel->where('debt_id', $id)->orderBy('tanggal', 'ASC')->findAll();

    // Hitung jumlah pinjaman dan tenor dari cicilan
    $totalCicilan = 0;
    foreach ($cicilan as $c) {
        $totalCicilan += $c['jumlah'];
    }
    $tenor = count($cicilan);

    // Override nilai di debt agar tampil di form
    $debt['jumlahPinjaman'] = $totalCicilan;
    $debt['jumlahTenor'] = $tenor;

    return view('debt/debt_edit', [
        'debt' => $debt,
        'cicilan' => $cicilan
    ]);
}

// Simpan perubahan nama aplikasi (jumlah & tenor otomatis)
public function update($id)
{
    $debtModel = new DebtModel();
    $data = [
        'namaAplikasi' => $this->request->getPost('namaAplikasi')
    ];
    $debtModel->update($id, $data);
    return redirect()->to('/debt/edit/'.$id)->with('success', 'Data hutang berhasil diperbarui.');
}

// Tambah cicilan baru
public function addCicilan($debt_id)
{
    $cicilanModel = new CicilanModel();
    $debtModel = new DebtModel();

    $data = [
        'debt_id' => $debt_id,
        'tanggal' => $this->request->getPost('tanggal'),
        'jumlah' => $this->request->getPost('jumlah')
    ];
    $cicilanModel->insert($data);

    // Update total pinjaman dan tenor di tabel debts
    $cicilans = $cicilanModel->where('debt_id', $debt_id)->findAll();
    $total = 0;
    foreach($cicilans as $c){
        $total += $c['jumlah'];
    }
    $debtModel->update($debt_id, [
        'jumlahPinjaman' => $total,
        'jumlahTenor' => count($cicilans)
    ]);

    return redirect()->to('/cicilan/edit/'.$debt_id)->with('success', 'Cicilan baru berhasil ditambahkan.');
}


// Hapus cicilan
public function deleteCicilan($id)
{
    $cicilanModel = new CicilanModel();
    $debtModel = new DebtModel();

    $cicilan = $cicilanModel->find($id);
    if(!$cicilan){
        return redirect()->back()->with('error', 'Cicilan tidak ditemukan.');
    }

    $debt_id = $cicilan['debt_id'];
    $cicilanModel->delete($id);

    // Update total pinjaman dan tenor setelah dihapus
    $cicilans = $cicilanModel->where('debt_id', $debt_id)->findAll();
    $total = 0;
    foreach($cicilans as $c){
        $total += $c['jumlah'];
    }
    $debtModel->update($debt_id, [
        'jumlahPinjaman' => $total,
        'jumlahTenor' => count($cicilans)
    ]);

    return redirect()->to('/cicilan/edit/'.$debt_id)->with('success', 'Cicilan berhasil dihapus.');
}

public function editCicilan($id)
{
    $cicilanModel = new CicilanModel();
    $cicilan = $cicilanModel->find($id);

    if (!$cicilan) {
        return redirect()->back()->with('error', 'Cicilan tidak ditemukan.');
    }

return view('debt/debt_edit_cicilan', [
    'cicilan' => $cicilan
]);

}


public function updateCicilan($id)
{
    $cicilanModel = new CicilanModel();
    $debtModel = new DebtModel();

    $cicilan = $cicilanModel->find($id);
    if (!$cicilan) {
        return redirect()->back()->with('error', 'Cicilan tidak ditemukan.');
    }

    $debt_id = $cicilan['debt_id'];

    // Update cicilan
    $cicilanModel->update($id, [
        'tanggal' => $this->request->getPost('tanggal'),
        'jumlah'  => $this->request->getPost('jumlah'),
    ]);

    // Recalculate total & tenor
    $all = $cicilanModel->where('debt_id', $debt_id)->findAll();
    $total = 0;
    foreach ($all as $c) {
        $total += $c['jumlah'];
    }
    $debtModel->update($debt_id, [
        'jumlahPinjaman' => $total,
        'jumlahTenor' => count($all)
    ]);

    return redirect()->to('/cicilan/edit_utama/'.$debt_id)->with('success', 'Cicilan berhasil diperbarui.');
}



}
