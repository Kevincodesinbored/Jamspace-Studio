document.addEventListener("DOMContentLoaded", () => {
    
    // ========== HELPER: Tampilkan Pesan Error ==========
    function showValidation(inputElement, message, isError = true) {
        // Hapus pesan lama jika ada
        let existing = inputElement.parentNode.querySelector(".dynamic-error");
        if (existing) existing.remove();

        if (message) {
            const errorDiv = document.createElement("div");
            errorDiv.className = `dynamic-error ${isError ? 'error' : 'success'}`;
            errorDiv.style.fontSize = "0.75rem";
            errorDiv.style.marginBottom = "15px";
            errorDiv.style.color = isError ? "#d33" : "#28a745";
            errorDiv.textContent = message;
            inputElement.after(errorDiv);
        }
    }

    // ========== TOGGLE PASSWORD VISIBILITY ==========
    const setupPasswordToggle = (buttonClass, inputId) => {
        const btn = document.querySelector(buttonClass);
        const input = document.getElementById(inputId);
        if (btn && input) {
            btn.addEventListener("click", () => {
                const icon = btn.querySelector("i");
                if (input.type === "password") {
                    input.type = "text";
                    icon.classList.replace("fa-eye", "fa-eye-slash");
                } else {
                    input.type = "password";
                    icon.classList.replace("fa-eye-slash", "fa-eye");
                }
            });
        }
    };

    setupPasswordToggle(".toggle-password", "password");
    setupPasswordToggle(".toggle-confirm-password", "confirmPassword");

    // ========== INPUT VALIDATIONS ==========
    
    // Email Validation
    const emailInput = document.getElementById("email");
    if (emailInput) {
        emailInput.addEventListener("blur", () => {
            const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailInput.value && !pattern.test(emailInput.value)) {
                showValidation(emailInput, "Please enter a valid email address");
            } else {
                showValidation(emailInput, null);
            }
        });
    }

    // Password Validation
    const passwordInput = document.getElementById("password");
    if (passwordInput) {
        passwordInput.addEventListener("blur", () => {
            if (passwordInput.value && passwordInput.value.length < 6) {
                showValidation(passwordInput, "Password must be at least 6 characters");
            } else {
                showValidation(passwordInput, null);
            }
        });
    }

    // Fullname Validation
    const fullnameInput = document.getElementById("fullname");
    if (fullnameInput) {
        fullnameInput.addEventListener("blur", () => {
            if (fullnameInput.value && fullnameInput.value.length < 3) {
                showValidation(fullnameInput, "Name must be at least 3 characters");
            } else {
                showValidation(fullnameInput, null);
            }
        });
    }

    // Phone Validation
    const phoneInput = document.getElementById("phone");
    if (phoneInput) {
        phoneInput.addEventListener("blur", () => {
            const pattern = /^[0-9+\-\s()]{8,}$/;
            if (phoneInput.value && !pattern.test(phoneInput.value)) {
                showValidation(phoneInput, "Please enter a valid phone number");
            } else {
                showValidation(phoneInput, null);
            }
        });
    }

    // Date Validation
    const dateInput = document.getElementById("date");
    if (dateInput) {
        const today = new Date().toISOString().split("T")[0];
        dateInput.setAttribute("min", today);
        dateInput.addEventListener("blur", () => {
            if (dateInput.value && dateInput.value < today) {
                showValidation(dateInput, "Please select a future date");
            } else {
                showValidation(dateInput, null);
            }
        });
    }
});
