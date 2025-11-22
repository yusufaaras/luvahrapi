// CV kayıtlarını admin panelinin tablosunda göstermek ve filtrelemek için JS

function fetchCVs() {
    const params = [];
    const expertise = document.getElementById('filterExpertise').value.trim();
    const name = document.getElementById('filterName').value.trim();
    const email = document.getElementById('filterEmail').value.trim();
    if (expertise) params.push('expertise=' + encodeURIComponent(expertise));
    if (name) params.push('name=' + encodeURIComponent(name));
    if (email) params.push('email=' + encodeURIComponent(email));
    const url = '/forms/cv-list.php' + (params.length ? '?' + params.join('&') : '');

    fetch(url)
        .then(r => r.json()).then(res => {
            if (res.status === 'success') {
                renderTable(res.data);
            } else {
                alert('Veri alınamadı: ' + res.message);
            }
        })
        .catch(() => alert('Sunucuya erişilemedi!'));
}

function renderTable(list) {
    const tbody = document.getElementById('cvsTableBody');
    tbody.innerHTML = '';
    list.forEach((item, i) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${i+1}</td>
            <td>${escapeHTML(item.name)}</td>
            <td>${escapeHTML(item.email)}</td>
            <td>${escapeHTML(item.phone || '')}</td>
            <td>${escapeHTML(item.expertise || '')}</td>
            <td>
                ${item.cv_url
                    ? `<a href="${item.cv_url}" target="_blank">${escapeHTML(item.original_filename || 'Dosya')}</a>`
                    : 'Yok'
                }
            </td>
            <td>${item.created_at ? new Date(item.created_at).toLocaleString('tr-TR') : ''}</td>
            <td>
                <button class="btn btn-sm btn-warning" onclick="editCV(${item.id})">Düzenle</button>
                <button class="btn btn-sm btn-danger" onclick="deleteCV(${item.id})">Sil</button>
            </td>
        `;
        tbody.append(tr);
    });
}

function escapeHTML(str) {
    return (str || '').replace(/[&<>"']/g, s => ({
        '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'
    }[s]));
}

// Basit olay tanımlamaları
document.getElementById('btnRefresh').onclick = fetchCVs;
document.getElementById('filterExpertise').oninput =
    document.getElementById('filterName').oninput =
    document.getElementById('filterEmail').oninput = function() {
        fetchCVs();
    };

// ilk yüklemede
fetchCVs();

// Dummy edit/delete fonksiyonları (geliştirilebilir)
function editCV(id) {
    alert('Düzenleme için backend ayrı eklenmeli: ' + id);
}

function deleteCV(id) {
    if (confirm('Silmek istiyor musunuz?')) {
        alert('Silme işlemi için backend ayrı eklenmeli: ' + id);
    }
}

// Excel export: SheetJS ile tabloyu Excel'e aktar
document.getElementById('btnExportExcel').onclick = function() {
    const table = document.getElementById('cvsTable');
    const wb = XLSX.utils.table_to_book(table, {sheet: 'CVs'});
    XLSX.writeFile(wb, 'cv_kayitlari.xlsx');
}