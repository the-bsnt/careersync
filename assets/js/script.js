document.addEventListener("DOMContentLoaded", function () {
  // Dark Mode Toggle
  const darkModeToggle = document.getElementById("darkModeToggle");
  const body = document.body;

  // Check for saved user preference
  const savedMode = localStorage.getItem("darkMode");
  if (savedMode === "enabled") {
    body.classList.add("dark-mode");
    darkModeToggle.textContent = "☀️";
  }

  darkModeToggle.addEventListener("click", function () {
    body.classList.toggle("dark-mode");
    const isDarkMode = body.classList.contains("dark-mode");

    if (isDarkMode) {
      localStorage.setItem("darkMode", "enabled");
      darkModeToggle.textContent = "☀️";
    } else {
      localStorage.setItem("darkMode", "disabled");
      darkModeToggle.textContent = "🌌";
    }
  });

  // Form Validation Enhancements
  const forms = document.querySelectorAll("form");
  forms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      let isValid = true;
      const requiredFields = form.querySelectorAll("[required]");

      requiredFields.forEach((field) => {
        if (!field.value.trim()) {
          field.classList.add("error");
          isValid = false;
        } else {
          field.classList.remove("error");
        }
      });

      // Special validation for email fields
      const emailFields = form.querySelectorAll('input[type="email"]');
      emailFields.forEach((email) => {
        if (email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
          email.classList.add("error");
          isValid = false;
        }
      });

      // Special validation for file inputs
      const fileInputs = form.querySelectorAll('input[type="file"]');
      fileInputs.forEach((input) => {
        if (input.required && !input.files.length) {
          input.classList.add("error");
          isValid = false;
        }
      });

      if (!isValid) {
        e.preventDefault();
        alert("Please fill in all required fields correctly.");
      }
    });
  });

  // Reset Filters Button
  const resetFilters = document.getElementById("resetFilters");
  if (resetFilters) {
    resetFilters.addEventListener("click", function () {
      const form = document.getElementById("filterForm");
      const inputs = form.querySelectorAll("input, select");

      inputs.forEach((input) => {
        if (input.type !== "submit" && input.type !== "button") {
          if (input.tagName === "SELECT") {
            input.selectedIndex = 0;
          } else {
            input.value = "";
          }
        }
      });

      form.submit();
    });
  }

  // Job Application Character Counter
  const coverLetter = document.getElementById("cover_letter");
  if (coverLetter) {
    const counter = document.createElement("small");
    counter.style.display = "block";
    counter.style.marginTop = "5px";
    coverLetter.parentNode.insertBefore(counter, coverLetter.nextSibling);

    coverLetter.addEventListener("input", function () {
      const minLength = 100;
      const currentLength = this.value.length;

      if (currentLength < minLength) {
        counter.textContent = `Minimum ${minLength} characters (${
          minLength - currentLength
        } more needed)`;
        counter.style.color = "#e74c3c";
      } else {
        counter.textContent = `${currentLength} characters (good!)`;
        counter.style.color = "#27ae60";
      }
    });

    // Trigger on page load
    coverLetter.dispatchEvent(new Event("input"));
  }

  // File Upload Preview (for resume upload)
  const fileInputs = document.querySelectorAll('input[type="file"]');
  fileInputs.forEach((input) => {
    input.addEventListener("change", function () {
      const fileName = this.files[0] ? this.files[0].name : "No file selected";
      const label = this.parentNode.querySelector("label");

      if (label) {
        const existingFileName = label.querySelector(".file-name");
        if (existingFileName) {
          existingFileName.textContent = fileName;
        } else {
          const fileNameSpan = document.createElement("span");
          fileNameSpan.className = "file-name";
          fileNameSpan.textContent = fileName;
          label.appendChild(document.createElement("br"));
          label.appendChild(fileNameSpan);
        }
      }

      // Remove error class if file is selected
      if (this.files.length) {
        this.classList.remove("error");
      }
    });
  });
});

// To fade out the alert message after some time.

setTimeout(function () {
  var msg = document.getElementsByClassName("alert");

  Array.from(msg).forEach(function (ms) {
    ms.style.animation = "fadeOut 0.5s ease forwards";
  });
}, 3000); // 3000ms = 3 seconds

//toggle the change status button;

// JavaScript
const statusToggleButton = document.querySelectorAll(".status");

statusToggleButton.forEach((button) => {
  button.addEventListener("click", function () {
    if (this.textContent.trim() === "Open") {
      this.textContent = "Close";
      this.classList.remove("safe");
      this.classList.add("danger");
    } else {
      this.textContent = "Open";
      this.classList.remove("danger");
      this.classList.add("safe");
    }
  });
});
