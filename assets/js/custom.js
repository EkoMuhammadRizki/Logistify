// AJAX Delete Data Barang
// Membutuhkan jQuery (di-include di dashboard.php) & SweetAlert2 (tema Bootstrap)
$(document).ready(function() {
    // Submit Form Barang Masuk (AJAX)
    $('#btnSubmitMasuk').on('click', function() {
        var form = document.getElementById('formMasuk');
        if (!form) { return; }
        var fd = new FormData(form);
        fd.append('action', 'create');
        $.ajax({
            url: 'proses_masuk.php',
            type: 'POST',
            data: fd,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        title: 'Berhasil',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK',
                        customClass: { confirmButton: 'btn btn-success' },
                        buttonsStyling: false
                    }).then(function(){
                        // Reset form dan jika ada modal, tutup
                        form.reset();
                        var modalEl = document.getElementById('modalMasuk');
                        if (modalEl && window.bootstrap) {
                            var bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                            bsModal.hide();
                        }
                        // Refresh grafik aktivitas stok bila ada
                        if (typeof window.refreshStockChart === 'function') { window.refreshStockChart(); }
                        // Tidak reload halaman; tabel dapat diperbarui manual jika diperlukan
                    });
                } else {
                    Swal.fire({ title: 'Gagal', text: response.message, icon: 'error', confirmButtonText: 'OK', customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false });
                }
            },
            error: function(){
                Swal.fire({ title: 'Error', text: 'Terjadi kesalahan komunikasi dengan server.', icon: 'error', confirmButtonText: 'OK', customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false });
            }
        });
    });

    // Fungsi Delete Data (AJAX) dengan SweetAlert2 bergaya Bootstrap
    $('.delete-btn').on('click', function() {
        var id_data = $(this).data('id');

        Swal.fire({
            title: 'Hapus data ini?',
            text: 'Aksi ini tidak dapat dibatalkan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal',
            customClass: {
                confirmButton: 'btn btn-danger',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'proses_data.php',
                    type: 'POST',
                    data: { delete_id: id_data },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                title: 'Berhasil',
                                text: response.message,
                                icon: 'success',
                                confirmButtonText: 'OK',
                                customClass: { confirmButton: 'btn btn-success' },
                                buttonsStyling: false
                            }).then(function() {
                                $('button[data-id="' + id_data + '"]').closest('tr').fadeOut(500, function() {
                                    $(this).remove();
                                });
                            });
                        } else {
                            Swal.fire({
                                title: 'Gagal menghapus',
                                text: response.message,
                                icon: 'error',
                                confirmButtonText: 'OK',
                                customClass: { confirmButton: 'btn btn-danger' },
                                buttonsStyling: false
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error',
                            text: 'Terjadi kesalahan komunikasi dengan server.',
                            icon: 'error',
                            confirmButtonText: 'OK',
                            customClass: { confirmButton: 'btn btn-danger' },
                            buttonsStyling: false
                        });
                    }
                });
            }
        });
    });

    // Delete transaksi Barang Masuk
    $(document).on('click', '.delete-masuk', function(){
        var id = $(this).closest('tr').data('id');
        Swal.fire({
            title: 'Hapus transaksi ini?', text:'Aksi ini tidak dapat dibatalkan.', icon:'warning', showCancelButton:true,
            confirmButtonText:'Ya, hapus', cancelButtonText:'Batal', customClass:{ confirmButton:'btn btn-danger', cancelButton:'btn btn-secondary' }, buttonsStyling:false
        }).then(function(result){
            if (!result.isConfirmed) return;
            $.ajax({
                url: 'proses_masuk.php?action=delete', type:'POST', data:{ id:id }, dataType:'json',
                success:function(resp){
                    if (resp.status === 'success'){
                        Swal.fire({ title:'Berhasil', text: resp.message, icon:'success', confirmButtonText:'OK', customClass:{ confirmButton:'btn btn-success' }, buttonsStyling:false })
                        .then(function(){ $('tr[data-id="'+id+'"]').fadeOut(300, function(){ $(this).remove(); }); if (typeof window.refreshStockChart === 'function') { window.refreshStockChart(); } });
                    } else {
                        Swal.fire({ title:'Gagal', text: resp.message, icon:'error', confirmButtonText:'OK', customClass:{ confirmButton:'btn btn-danger' }, buttonsStyling:false });
                    }
                },
                error:function(){ Swal.fire({ title:'Error', text:'Terjadi kesalahan komunikasi dengan server.', icon:'error', confirmButtonText:'OK', customClass:{ confirmButton:'btn btn-danger' }, buttonsStyling:false }); }
            });
        });
    });
});