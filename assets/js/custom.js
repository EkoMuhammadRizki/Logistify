// Membutuhkan jQuery (sudah di-include di index.php)
$(document).ready(function() {
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
});