// Oturum kontrolü ve tablo listelemeyi başlat
async function loadTable() {
    const fd = new FormData();
    fd.append("action", "list");
    fd.append("expertise", document.getElementById("filterExpertise").value);
    fd.append("name", document.getElementById("filterName").value);
    fd.append("email", document.getElementById("filterEmail").value);
    const resp = await fetch('/admin-api.php', {method:'POST', body:fd });
    const json = await resp.json();
    if(json.status !== "success") {
        alert(json.message); return;
    }
    const tbody = document.getElementById("cvsTableBody");
    tbody.innerHTML = "";
    json.rows.forEach((row, idx) => {
        tbody.innerHTML += `
        <tr>
          <td>${row.id}</td>
          <td>${row.name}</td>
          <td>${row.email}</td>
          <td>${row.phone}</td>
          <td>${row.expertise||''}</td>
          <td><a href="/uploads/cvs/${row.stored_filename}" target="_blank">${row.original_filename}</a></td>
          <td>${row.created_at}</td>
          <td>
            <button class="btn btn-sm btn-warning me-1" onclick="editRow(${row.id}, '${row.name}', '${row.email}', '${row.phone}', '${row.expertise||''}')">Düzenle</button>
            <button class="btn btn-sm btn-danger" onclick="deleteRow(${row.id})">Sil</button>
          </td>
        </tr>`;
    });
}

// Excel export
document.getElementById("btnExportExcel").onclick = async function() {
    const fd = new FormData();
    fd.append("action", "list");
    const resp = await fetch('/admin-api.php', {method:'POST',body:fd});
    const json = await resp.json();
    if(json.status !== "success") { alert(json.message); return; }
    const sheet = XLSX.utils.json_to_sheet(json.rows);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, sheet, 'CVs');
    XLSX.writeFile(wb, 'CV_Kayitlari.xlsx');
};

// Filtre & Yenile
document.getElementById("btnRefresh").onclick = loadTable;
["filterExpertise","filterName","filterEmail"].forEach(id => {
    document.getElementById(id).onchange = loadTable;
});
window.onload = loadTable;

// Silme
async function deleteRow(id) {
    if(!confirm("Silmek istediğinize emin misiniz?")) return;
    const fd = new FormData(); fd.append("action","delete"); fd.append("id",id);
    const resp = await fetch('/admin-api.php',{method:'POST',body:fd});
    const json = await resp.json();
    if(json.status==="success") loadTable();
    else alert(json.message);
}

// Düzenleme modalı
function editRow(id,name,email,phone,expertise) {
    document.getElementById('editId').value=id;
    document.getElementById('editName').value=name;
    document.getElementById('editEmail').value=email;
    document.getElementById('editPhone').value=phone;
    document.getElementById('editExpertise').value=expertise;
    var modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
}
document.getElementById('editForm').onsubmit = async function(e) {
    e.preventDefault();
    const fd = new FormData();
    fd.append("action","update");
    fd.append("id",document.getElementById('editId').value);
    fd.append("name",document.getElementById('editName').value);
    fd.append("email",document.getElementById('editEmail').value);
    fd.append("phone",document.getElementById('editPhone').value);
    fd.append("expertise",document.getElementById('editExpertise').value);
    const resp = await fetch('/admin-api.php',{method:'POST',body:fd});
    const json = await resp.json();
    if(json.status==="success") { loadTable(); bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();}
    else alert(json.message);
};