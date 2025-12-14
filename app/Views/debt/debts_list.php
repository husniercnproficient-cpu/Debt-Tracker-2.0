<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>DebtTracker</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 30px; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    button { padding: 5px; margin: 2px; }
    h2 { margin-top: 20px; }
  </style>
</head>
<body>
  <h1>DebtTracker</h1>
  <a href="<?= site_url('add'); ?>"><button>Tambah Debt Baru</button></a>

  <div id="debtContainer"></div>

  <script>
    function formatDate(iso) {
      const d = new Date(iso);
      return d.toLocaleDateString('id-ID');
    }

    async function loadData() {
      const res = await fetch('<?= site_url("api/debts") ?>');
      const data = await res.json();

      const container = document.getElementById('debtContainer');
      container.innerHTML = '';

      data.forEach(debt => {
        const div = document.createElement('div');
        div.innerHTML = `
          <h2>${debt.namaAplikasi}</h2>
          <p>Jumlah Pinjaman: Rp ${debt.jumlahPinjaman.toLocaleString()}</p>
          <p>Jumlah Tenor: ${debt.jumlahTenor}</p>

          <a href="<?= site_url('edit'); ?>?id=${debt.id}">
            <button>Edit Debt & Cicilan</button>
          </a>

          <button onclick="deleteDebt(${debt.id})">Hapus Debt</button>

          <h3>Daftar Cicilan</h3>
          <table>
            <tr><th>Tanggal</th><th>Jumlah</th></tr>
            ${debt.cicilan.map(c => `
              <tr>
                <td>${formatDate(c.tanggal)}</td>
                <td>Rp ${c.jumlah.toLocaleString()}</td>
              </tr>
            `).join('')}
          </table>
        `;
        container.appendChild(div);
      });
    }

    async function deleteDebt(id) {
      if (!confirm("Hapus debt ini?")) return;
      await fetch(`<?= site_url('api/debts') ?>/${id}`, { method: 'DELETE' });
      loadData();
    }

    loadData();
  </script>
</body>
</html>
