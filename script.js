let unsaved = false;

function setSaveStatus(text, color) {
    const el = document.getElementById("saveStatus");
    if (el) { el.textContent = text; el.style.color = color; }

    
}

function showPassword(button) {
    const password = document.getElementById("myPassword");
    if (!password) return;

    const isVisible = password.type === "password";
    password.type = isVisible ? "text" : "password";

    if (button) {
        const icon = button.querySelector("i");
        if (icon) {
            icon.classList.toggle("fa-eye", !isVisible);
            icon.classList.toggle("fa-eye-slash", isVisible);
        }
        button.setAttribute("aria-label", isVisible ? "Hide password" : "Show password");
        button.setAttribute("aria-pressed", String(isVisible));
    }
}

const profile = document.querySelector(".profile");
const profileButton = document.querySelector(".drop-btn");
if (profile && profileButton) {
    const setProfileExpanded = expanded => {
        profileButton.setAttribute("aria-expanded", String(expanded));
    };

    profile.addEventListener("pointerenter", () => setProfileExpanded(true));
    profile.addEventListener("pointerleave", () => {
        setProfileExpanded(profile.contains(document.activeElement));
    });
    profile.addEventListener("focusin", () => setProfileExpanded(true));
    profile.addEventListener("focusout", () => {
        setProfileExpanded(profile.matches(":hover") || profile.contains(document.activeElement));
    });
}

const notifBadge = document.getElementById("notif-badge");
const notificationButton = document.getElementById("notif-btn");
const notificationDropdown = document.getElementById("notificationDropdown");
const notifList = document.getElementById("notif-list");
const clearBtn = document.getElementById("clear-btn");

const notificationContainer = notificationButton?.closest(".notification");
if (notificationButton && notificationDropdown && notificationContainer) {
    const setNotificationOpen = open => {
        notificationContainer.classList.toggle("is-open", open);
        notificationButton.setAttribute("aria-expanded", String(open));
    };

    notificationButton.addEventListener("click", event => {
        event.stopPropagation();
        setNotificationOpen(notificationButton.getAttribute("aria-expanded") !== "true");
    });

    document.addEventListener("click", event => {
        if (!notificationContainer.contains(event.target)) setNotificationOpen(false);
    });

    document.addEventListener("keydown", event => {
        if (event.key === "Escape" && notificationButton.getAttribute("aria-expanded") === "true") {
            setNotificationOpen(false);
            notificationButton.focus();
        }
    });
}

if (clearBtn && notifList && notifBadge) {
    clearBtn.addEventListener("click", () => {
        clearBtn.disabled = true;
        fetch("get_notification.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ action: "mark_read" })
        })
            .then(response => {
                if (!response.ok) throw new Error("Notification update failed.");
                return response.json();
            })
            .then(result => {
                if (!result.ok) throw new Error("Notification update was rejected.");
                notifList.querySelectorAll(".unread").forEach(item => item.classList.remove("unread"));
                notifBadge.textContent = "0";
                notifBadge.hidden = true;
            })
            .catch(error => {
                console.error("Could not clear notifications.", error);
                showToast("Could not clear notifications. Please try again.", true);
            })
            .finally(() => { clearBtn.disabled = false; });
    });
}

function formatIC(input) {
      
        let value = input.value.replace(/\D/g, '');
        
        if (value.length > 6 && value.length <= 8) {
            value = value.slice(0, 6) + '-' + value.slice(6);
        } else if (value.length > 8) {
            value = value.slice(0, 6) + '-' + value.slice(6, 8) + '-' + value.slice(8, 12);
        }
        
        input.value = value;
    }

function formatPhone(input) {
       
        let value = input.value.replace(/\D/g, '');
        
        if (value.length > 3 && value.length <= 7) {
            value = value.slice(0, 3) + '-' + value.slice(3);
        } else if (value.length > 7) {
            value = value.slice(0, 3) + '-' + value.slice(3, 7) + ' ' + value.slice(7, 11);
        }
        
        input.value = value;
    }

const filter = document.querySelector(".filter");
const filterButton = filter?.querySelector(".filter-button");
if (filter && filterButton) {
    const setFilterOpen = open => {
        filter.classList.toggle("is-open", open);
        filterButton.setAttribute("aria-expanded", String(open));
    };

    filterButton.addEventListener("click", () => {
        setFilterOpen(filterButton.getAttribute("aria-expanded") !== "true");
    });

    document.addEventListener("click", event => {
        if (!filter.contains(event.target)) setFilterOpen(false);
    });

    document.addEventListener("keydown", event => {
        if (event.key === "Escape" && filterButton.getAttribute("aria-expanded") === "true") {
            setFilterOpen(false);
            filterButton.focus();
        }
    });
}


function toggleStatus(button) {
    if (button.classList.contains("absent")) {
        button.classList.remove("absent");
        button.classList.add("present");
        button.innerHTML = "✓ Present";
    } else {
        button.classList.remove("present");
        button.classList.add("absent");
        button.innerHTML = "✕ Absent";
    }
    unsaved = true;
    setSaveStatus("Unsaved changes", "#d97706");
}


const attsavedMsg = '<i class="fa-solid fa-circle-check"></i> Attendance saved successfully';

const errorMsg = '<i class="fa-solid fa-circle-xmark"></i> Failed to save attendance. Please try again.';

const addstudentMsg = '<i class="fa-solid fa-circle-check"></i> Student added successfully';

const deleteRecordMsg = '<i class="fa-solid fa-circle-check"></i> Record deleted successfully';

const addcancelMsg = '<i class="fa-solid fa-circle-xmark"></i> Add student cancelled';

const editstudentMsg = '<i class="fa-solid fa-circle-check"></i> Student details updated successfully';

const editcancelMsg = '<i class="fa-solid fa-circle-xmark"></i> Edit cancelled';

function showToast(msg, isError = false) {
    const toastBox = document.getElementById('toastBox');
    if (!toastBox) return;

    const toast = document.createElement('div');
    toast.classList.add('toast');
    if (isError) toast.classList.add('error');
    toast.setAttribute('role', isError ? 'alert' : 'status');

    const message = document.createElement('span');
    message.classList.add('toast-message');
    message.innerHTML = msg;

    const closeButton = document.createElement('button');
    closeButton.classList.add('toast-close');
    closeButton.type = 'button';
    closeButton.setAttribute('aria-label', 'Dismiss notification');
    closeButton.textContent = '×';
    closeButton.addEventListener('click', () => toast.remove());

    toast.append(message, closeButton);
    toastBox.appendChild(toast);
}

function saveAll() {
    const records = [...document.querySelectorAll(".status-btn")].map(b => ({
        student_id: b.dataset.id,
        status: b.classList.contains("present") ? "Present" : "Absent"
    }));
    const btn = document.getElementById("saveAllBtn");
    btn.disabled = true;
    setSaveStatus("Saving...", "#6b7280");

    fetch("updateAttendance.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ records: records })
    })
        .then(r => r.json())
        .then(d => {
        if (!d.ok) throw new Error("save failed");
        unsaved = false;
        const t = new Date().toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
        setSaveStatus("✓ Saved at " + t, "#16a34a");
        // FIX: replaced alert() with the toast, called only AFTER save succeeds
        showToast(attsavedMsg);
    })
    .catch(() => {
        setSaveStatus("Save failed, try again", "#dc2626");
        // FIX: replaced alert() with the error toast
        showToast(errorMsg, true);
    })
    .finally(() => { btn.disabled = false; });
}


window.addEventListener("beforeunload", e => {
    if (unsaved) { e.preventDefault(); e.returnValue = ""; }
});

function previewImage(event) {
    const input = event.target;
    const fileName = input.closest(".file-upload")?.querySelector(".file-upload-name");
    const file = input.files?.[0];
    if (fileName) fileName.textContent = file?.name || "No file chosen";

    const preview = input.form?.querySelector(".photo-preview");
    if (preview && file) {
        if (preview.dataset.previewUrl) URL.revokeObjectURL(preview.dataset.previewUrl);
        preview.dataset.previewUrl = URL.createObjectURL(file);
        preview.src = preview.dataset.previewUrl;
        const previewButton = preview.closest(".photo-preview-button");
        if (previewButton) previewButton.hidden = false;
        else preview.hidden = false;
    }
}

const photoCropModal = document.getElementById("profilePhotoCropModal");
const photoCropButtons = document.querySelectorAll(".photo-preview-button");
const photoCropCanvas = photoCropModal?.querySelector(".photo-crop-canvas");
const photoCropZoom = photoCropModal?.querySelector("#profilePhotoZoom");

if (photoCropModal && photoCropButtons.length && photoCropCanvas && photoCropZoom) {
    const cropContext = photoCropCanvas.getContext("2d");
    const cropImage = new Image();
    const cropScale = { base: 1, current: 1, x: 0, y: 0, dragging: false, lastX: 0, lastY: 0 };
    const cropApplyButton = photoCropModal.querySelector("[data-crop-apply]");
    const cropCancelButton = photoCropModal.querySelector("[data-crop-cancel]");
    let activeCropButton = null;
    let activePhotoInput = null;

    const drawCropImage = () => {
        if (!cropContext || !cropImage.complete || !cropImage.naturalWidth) return;
        const width = cropImage.naturalWidth * cropScale.current;
        const height = cropImage.naturalHeight * cropScale.current;
        cropScale.x = Math.min(0, Math.max(photoCropCanvas.width - width, cropScale.x));
        cropScale.y = Math.min(0, Math.max(photoCropCanvas.height - height, cropScale.y));
        cropContext.clearRect(0, 0, photoCropCanvas.width, photoCropCanvas.height);
        cropContext.drawImage(cropImage, cropScale.x, cropScale.y, width, height);
    };

    const closeCropEditor = () => {
        photoCropModal.hidden = true;
        cropScale.dragging = false;
        activeCropButton?.focus();
    };

    photoCropButtons.forEach(button => {
        button.addEventListener("click", () => {
            const photoInput = button.form?.querySelector('input[type="file"][name="photo"]');
            const file = photoInput?.files?.[0];
            if (!file || !photoInput) return;
            activeCropButton = button;
            activePhotoInput = photoInput;
            const imageUrl = URL.createObjectURL(file);
            cropImage.onload = () => {
                URL.revokeObjectURL(imageUrl);
                const canvasSize = photoCropCanvas.width;
                cropScale.base = Math.max(canvasSize / cropImage.naturalWidth, canvasSize / cropImage.naturalHeight);
                cropScale.current = cropScale.base;
                cropScale.x = (canvasSize - cropImage.naturalWidth * cropScale.current) / 2;
                cropScale.y = (canvasSize - cropImage.naturalHeight * cropScale.current) / 2;
                photoCropZoom.value = "1";
                drawCropImage();
                photoCropModal.hidden = false;
                cropCancelButton.focus();
            };
            cropImage.onerror = () => {
                URL.revokeObjectURL(imageUrl);
                showToast('<i class="fa-solid fa-circle-xmark"></i> Could not open this photo. Please choose another file.', true);
            };
            cropImage.src = imageUrl;
        });
    });

    photoCropZoom.addEventListener("input", () => {
        const oldScale = cropScale.current;
        const centerX = photoCropCanvas.width / 2;
        const centerY = photoCropCanvas.height / 2;
        cropScale.current = cropScale.base * Number(photoCropZoom.value);
        cropScale.x = centerX - (centerX - cropScale.x) * cropScale.current / oldScale;
        cropScale.y = centerY - (centerY - cropScale.y) * cropScale.current / oldScale;
        drawCropImage();
    });

    photoCropCanvas.addEventListener("pointerdown", event => {
        cropScale.dragging = true;
        cropScale.lastX = event.clientX;
        cropScale.lastY = event.clientY;
        photoCropCanvas.setPointerCapture(event.pointerId);
    });

    photoCropCanvas.addEventListener("pointermove", event => {
        if (!cropScale.dragging) return;
        const bounds = photoCropCanvas.getBoundingClientRect();
        cropScale.x += (event.clientX - cropScale.lastX) * photoCropCanvas.width / bounds.width;
        cropScale.y += (event.clientY - cropScale.lastY) * photoCropCanvas.height / bounds.height;
        cropScale.lastX = event.clientX;
        cropScale.lastY = event.clientY;
        drawCropImage();
    });

    photoCropCanvas.addEventListener("pointerup", () => { cropScale.dragging = false; });
    photoCropCanvas.addEventListener("pointercancel", () => { cropScale.dragging = false; });
    cropCancelButton.addEventListener("click", closeCropEditor);
    photoCropModal.addEventListener("click", event => {
        if (event.target === photoCropModal) closeCropEditor();
    });

    cropApplyButton.addEventListener("click", () => {
        if (!cropContext || !cropImage.naturalWidth || !activePhotoInput || !activeCropButton) return;
        const outputCanvas = document.createElement("canvas");
        outputCanvas.width = 512;
        outputCanvas.height = 512;
        const sourceX = -cropScale.x / cropScale.current;
        const sourceY = -cropScale.y / cropScale.current;
        const sourceSize = photoCropCanvas.width / cropScale.current;
        const outputContext = outputCanvas.getContext("2d");
        if (!outputContext) {
            showToast('<i class="fa-solid fa-circle-xmark"></i> Could not crop this photo.', true);
            return;
        }
        outputContext.drawImage(cropImage, sourceX, sourceY, sourceSize, sourceSize, 0, 0, outputCanvas.width, outputCanvas.height);
        outputCanvas.toBlob(blob => {
            if (!blob) {
                showToast('<i class="fa-solid fa-circle-xmark"></i> Could not crop this photo.', true);
                return;
            }
            const croppedFile = new File([blob], "profile-photo.jpg", { type: "image/jpeg" });
            const transfer = new DataTransfer();
            transfer.items.add(croppedFile);
            activePhotoInput.files = transfer.files;
            const fileName = activePhotoInput.closest(".file-upload")?.querySelector(".file-upload-name");
            if (fileName) fileName.textContent = croppedFile.name;

            const preview = activeCropButton.querySelector(".photo-preview");
            if (preview.dataset.previewUrl) URL.revokeObjectURL(preview.dataset.previewUrl);
            preview.dataset.previewUrl = URL.createObjectURL(croppedFile);
            preview.src = preview.dataset.previewUrl;
            closeCropEditor();
        }, "image/jpeg", 0.92);
    });

    document.addEventListener("keydown", event => {
        if (event.key === "Escape" && !photoCropModal.hidden) closeCropEditor();
    });
}

// ===== Popup modal (only runs on pages that have one) =====
const modals = document.querySelectorAll(".modal");
const openBtns = document.querySelectorAll("#modalBtn, .open-modal");

if (modals.length && openBtns.length) {
    const closeModal = modal => { modal.style.display = "none"; };

    openBtns.forEach(btn => {
        btn.addEventListener("click", e => {
            e.preventDefault();
            const modal = document.getElementById(btn.dataset.modalTarget || "myModal");
            if (!modal) return;

            // fill any [data-field] in the modal from the button's data-* attributes
            modal.querySelectorAll("[data-field]").forEach(el => {
                const value = btn.dataset[el.dataset.field] ?? "";
                if (el instanceof HTMLImageElement && el.dataset.field === "photo") {
                    const previewButton = el.closest(".photo-preview-button");
                    if (value) {
                        el.src = value;
                        el.hidden = false;
                        if (previewButton) previewButton.hidden = false;
                        el.onerror = () => {
                            el.hidden = true;
                            if (previewButton) previewButton.hidden = true;
                        };
                    } else {
                        el.removeAttribute("src");
                        el.hidden = true;
                        if (previewButton) previewButton.hidden = true;
                    }
                } else if (el.matches("input, select, textarea")) {
                    el.value = value;
                } else {
                    el.textContent = value;
                }
            });
            modal.style.display = "block";
        });
    });

    modals.forEach(modal => {
        modal.querySelectorAll(".close, #cancelModalBtn").forEach(btn => {
            btn.addEventListener("click", () => closeModal(modal));
        });
    });

    // click outside the form to close
    window.addEventListener("click", e => {
        modals.forEach(modal => {
            if (e.target === modal) closeModal(modal);
        });
    });

    // Esc key to close
    document.addEventListener("keydown", e => {
        if (e.key === "Escape") modals.forEach(closeModal);
    });

}
