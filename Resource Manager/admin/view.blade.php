<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <strong><i class="fa fa-upload"></i> Upload Images</strong>
                </h3>
            </div>
            <div class="box-body">
                <form id="upload-form">
                    <div class="form-group">
                        <label for="image">Select Image</label>
                        <input type="file" id="image" name="image" class="form-control" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </form>
                <hr>
                <h4>Uploaded Images</h4>
                <ul id="image-list" style="list-style: none; padding: 0;">
                    
                </ul>
            </div>
        </div>
    </div>
</div>

<div id="delete-modal" class="modal">
    <div class="modal-content">
        <h4>Confirm Deletion</h4>
        <p>Are you sure you want to delete this image?</p>
        <div class="modal-actions">
            <button id="confirm-delete" class="btn btn-danger">Delete</button>
            <button id="cancel-delete" class="btn btn-secondary">Cancel</button>
        </div>
    </div>
</div>

<style>
        .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }

    .modal-content {
        background: #060505;
        padding: 20px;
        border-radius: 5px;
        text-align: center;
        width: 300px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .modal-actions {
        margin-top: 20px;
        display: flex;
        justify-content: space-between;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .btn-danger {
        background-color: #dc3545;
        color: #fff;
    }

    .btn-secondary {
        background-color: #6c757d;
        color: #fff;
    }

    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
    }

    .toast {
        display: flex;
        align-items: center;
        background-color: #333;
        color: #fff;
        padding: 10px 20px;
        margin-bottom: 10px;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        opacity: 0;
        transform: translateY(-20px);
        transition: opacity 0.3s ease, transform 0.3s ease;
    }

    .toast.show {
        opacity: 1;
        transform: translateY(0);
    }

    .toast.success {
        background-color: #28a745;
    }

    .toast.error {
        background-color: #dc3545;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const uploadForm = document.getElementById('upload-form');
        const imageList = document.getElementById('image-list');
        const deleteModal = document.getElementById('delete-modal');
        const confirmDeleteButton = document.getElementById('confirm-delete');
        const cancelDeleteButton = document.getElementById('cancel-delete');
        let deleteCallback = null;

        function showModal(callback) {
            deleteCallback = callback;
            deleteModal.style.display = 'flex';
        }

        function hideModal() {
            deleteModal.style.display = 'none';
            deleteCallback = null;
        }

        function showToast(message, type = 'success') {
            const toastContainer = document.querySelector('.toast-container') || createToastContainer();
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.textContent = message;

            toastContainer.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('show');
            }, 100);

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        function createToastContainer() {
            const container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
            return container;
        }

        const fetchImages = async () => {
            const response = await fetch('{{ route('blueprint.extensions.resourcemanager.listImages') }}');
            const data = await response.json();

            if (data.success) {
                imageList.innerHTML = '';
                data.files.forEach(file => {
                    const listItem = document.createElement('li');
                    listItem.style.marginBottom = '10px';

                    const img = document.createElement('img');
                    img.src = file.url;
                    img.alt = file.name;
                    img.style.width = '100px';
                    img.style.marginRight = '10px';

                    const copyButton = document.createElement('button');
                    copyButton.textContent = 'Copy Link';
                    copyButton.className = 'btn btn-sm btn-info';
                    copyButton.style.marginRight = '5px';
                    copyButton.addEventListener('click', () => {
                        navigator.clipboard.writeText(file.url);
                        showToast('Link copied to clipboard!', 'success');
                    });

                    const deleteButton = document.createElement('button');
                    deleteButton.textContent = 'Delete';
                    deleteButton.className = 'btn btn-sm btn-danger';
                    deleteButton.addEventListener('click', () => {
                        showModal(async () => {
                            const deleteResponse = await fetch('{{ route('blueprint.extensions.resourcemanager.deleteImage') }}', {
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                                body: JSON.stringify({ filename: file.name }),
                            });

                            const deleteData = await deleteResponse.json();
                            if (deleteData.success) {
                                showToast('Image deleted successfully.', 'success');
                                fetchImages();
                            } else {
                                showToast('Failed to delete image.', 'error');
                            }
                            hideModal();
                        });
                    });

                    listItem.appendChild(img);
                    listItem.appendChild(copyButton);
                    listItem.appendChild(deleteButton);
                    imageList.appendChild(listItem);
                });
            }
        };

        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(uploadForm);
            const response = await fetch('{{ route('blueprint.extensions.resourcemanager.uploadImage') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: formData,
            });

            const data = await response.json();
            if (data.success) {
                showToast(data.message, 'success');
                fetchImages();
            } else {
                showToast('Failed to upload image.', 'error');
            }
        });

        confirmDeleteButton.addEventListener('click', () => {
            if (deleteCallback) deleteCallback();
        });

        cancelDeleteButton.addEventListener('click', hideModal);

        fetchImages();
    });
</script>