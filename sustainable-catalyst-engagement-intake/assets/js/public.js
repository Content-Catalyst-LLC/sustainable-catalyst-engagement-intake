(() => {
  "use strict";

  const config = window.scEiPublic || {};
  const selectors = {
    form: "[data-sc-ei-form]",
    step: "[data-sc-ei-step]",
    next: "[data-sc-ei-next]",
    back: "[data-sc-ei-back]",
    type: "[data-sc-ei-type]",
    contactMethod: "[data-sc-ei-contact-method]",
    meetingRequest: "[data-sc-ei-meeting-request]",
    timezone: "[data-sc-ei-timezone]",
    route: "[data-sc-ei-route]",
    conditional: "[data-show-for]",
    contactConditional: "[data-contact-method-show]",
    meetingConditional: "[data-meeting-request-show]",
    errors: "[data-sc-ei-errors]",
    progressBar: "[data-sc-ei-progress-bar]",
    progressStep: "[data-sc-ei-progress-step]",
    reviewList: "[data-sc-ei-review-list]",
    success: "[data-sc-ei-success]",
    reference: "[data-sc-ei-reference]",
    submit: "[data-sc-ei-submit]"
  };

  const visible = (element) => !element.hidden && element.offsetParent !== null;
  const values = (attribute) => (attribute || "").split(",").map((item) => item.trim()).filter(Boolean);

  class EngagementForm {
    constructor(form) {
      this.form = form;
      this.step = 1;
      this.steps = Array.from(form.querySelectorAll(selectors.step));
      this.errors = form.querySelector(selectors.errors);
      this.type = form.querySelector(selectors.type);
      this.contactMethod = form.querySelector(selectors.contactMethod);
      this.meetingRequest = form.querySelector(selectors.meetingRequest);
      this.success = form.querySelector(selectors.success);
      this.submitButton = form.querySelector(selectors.submit);
      this.bind();
      this.suggestTimezone();
      this.applyConditions();
      this.showStep(1);
    }

    bind() {
      this.form.addEventListener("click", (event) => {
        const next = event.target.closest(selectors.next);
        const back = event.target.closest(selectors.back);
        if (next) {
          event.preventDefault();
          this.next();
        }
        if (back) {
          event.preventDefault();
          this.showStep(Math.max(1, this.step - 1));
        }
      });

      this.form.addEventListener("change", (event) => {
        if (
          event.target.matches(selectors.type) ||
          event.target.matches(selectors.contactMethod) ||
          event.target.matches(selectors.meetingRequest)
        ) {
          this.applyConditions();
        }
      });

      this.form.addEventListener("submit", (event) => {
        if (!window.fetch || !config.restUrl) return;
        event.preventDefault();
        this.submit();
      });

      const hub = this.form.closest("[data-sc-ei-hub]");
      if (hub) {
        hub.querySelectorAll(selectors.route).forEach((button) => {
          button.addEventListener("click", () => {
            const value = button.getAttribute("data-sc-ei-route");
            if (this.type && Array.from(this.type.options).some((option) => option.value === value)) {
              this.type.value = value;
              this.type.dispatchEvent(new Event("change", { bubbles: true }));
              this.form.scrollIntoView({ behavior: "smooth", block: "start" });
              this.type.focus({ preventScroll: true });
            }
          });
        });
      }
    }

    suggestTimezone() {
      const field = this.form.querySelector(selectors.timezone);
      if (!field || field.value) return;
      try {
        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        if (timezone) field.value = timezone;
      } catch (error) {
        // Manual entry remains available.
      }
    }

    setConditional(container, shouldShow) {
      container.hidden = !shouldShow;
      container.querySelectorAll("input, select, textarea").forEach((field) => {
        field.disabled = !shouldShow;
        if (field.hasAttribute("data-required-when-visible")) {
          field.required = shouldShow;
        }
      });
    }

    applyConditions() {
      const type = this.type?.value || "";
      const contact = this.contactMethod?.value || "email";
      const meeting = this.meetingRequest?.value || "no";

      this.form.querySelectorAll(selectors.conditional).forEach((container) => {
        this.setConditional(container, values(container.getAttribute("data-show-for")).includes(type));
      });

      this.form.querySelectorAll(selectors.contactConditional).forEach((container) => {
        this.setConditional(container, values(container.getAttribute("data-contact-method-show")).includes(contact));
      });

      this.form.querySelectorAll(selectors.meetingConditional).forEach((container) => {
        this.setConditional(container, values(container.getAttribute("data-meeting-request-show")).includes(meeting));
      });

      const teamsEmail = this.form.querySelector("[name='teams_email']");
      const primaryEmail = this.form.querySelector("[name='contact_email']");
      if ((meeting === "yes" || meeting === "unsure") && teamsEmail && !teamsEmail.value && primaryEmail?.value) {
        teamsEmail.value = primaryEmail.value;
      }
    }

    next() {
      if (!this.validateStep(this.step)) return;
      if (this.step === 2) this.buildReview();
      this.showStep(Math.min(this.steps.length, this.step + 1));
    }

    showStep(step) {
      this.step = step;
      this.steps.forEach((section) => {
        const active = Number(section.getAttribute("data-sc-ei-step")) === step;
        section.hidden = !active;
        section.classList.toggle("is-active", active);
      });

      const bar = this.form.querySelector(selectors.progressBar);
      if (bar) bar.style.width = `${((step - 1) / (this.steps.length - 1)) * 100}%`;

      this.form.querySelectorAll(selectors.progressStep).forEach((item) => {
        const number = Number(item.getAttribute("data-sc-ei-progress-step"));
        item.classList.toggle("is-active", number === step);
        item.classList.toggle("is-complete", number < step);
      });

      const activeStep = this.steps.find((section) => Number(section.getAttribute("data-sc-ei-step")) === step);
      const legend = activeStep?.querySelector("legend");
      if (legend) {
        legend.setAttribute("tabindex", "-1");
        legend.focus({ preventScroll: true });
      }
      this.clearErrors();
    }

    validateStep(step) {
      const section = this.steps.find((item) => Number(item.getAttribute("data-sc-ei-step")) === step);
      if (!section) return true;

      const invalid = Array.from(section.querySelectorAll("input, select, textarea"))
        .filter((field) => !field.disabled && visible(field) && !field.checkValidity());

      if (!invalid.length) {
        this.clearErrors();
        return true;
      }

      invalid.forEach((field) => field.setAttribute("aria-invalid", "true"));
      const messages = invalid.map((field) => {
        const label = field.id ? this.form.querySelector(`label[for="${CSS.escape(field.id)}"]`) : field.closest("label");
        return label ? label.textContent.replace("*", "").trim() : field.name;
      });

      this.showErrors(messages);
      invalid[0].focus();
      return false;
    }

    buildReview() {
      const list = this.form.querySelector(selectors.reviewList);
      if (!list) return;
      list.innerHTML = "";

      const processedCheckboxGroups = new Set();
      const fields = Array.from(this.form.elements).filter((field) => {
        if (!field.name || field.disabled || !visible(field)) return false;
        if (["hidden", "submit", "button"].includes(field.type)) return false;
        if ((field.type === "checkbox" || field.type === "radio") && !field.checked) return false;
        return Boolean(field.value);
      });

      fields.forEach((field) => {
        if (["privacy_consent", "authorization_consent", "follow_up_consent", "calendar_invite_consent", "company_website"].includes(field.name)) return;

        if (field.name === "preferred_weekdays[]") {
          if (processedCheckboxGroups.has(field.name)) return;
          processedCheckboxGroups.add(field.name);
          const checked = Array.from(this.form.querySelectorAll("[name='preferred_weekdays[]']:checked"))
            .map((item) => item.closest("label")?.textContent.trim() || item.value);
          if (!checked.length) return;
          this.addReviewItem(list, "Preferred weekdays", checked.join(", "));
          return;
        }

        const label = field.id ? this.form.querySelector(`label[for="${CSS.escape(field.id)}"]`) : null;
        if (!label) return;

        const displayValue = field.tagName === "SELECT"
          ? (field.options[field.selectedIndex]?.text || field.value)
          : field.value;
        this.addReviewItem(list, label.textContent.replace("*", "").trim(), displayValue);
      });
    }

    addReviewItem(list, label, value) {
      const dt = document.createElement("dt");
      const dd = document.createElement("dd");
      dt.textContent = label;
      dd.textContent = value;
      list.append(dt, dd);
    }

    showErrors(messages) {
      if (!this.errors) return;
      this.errors.hidden = false;
      this.errors.innerHTML = "";
      const strong = document.createElement("strong");
      strong.textContent = config.i18n?.validationHeading || "Review these fields:";
      const ul = document.createElement("ul");
      [...new Set(messages)].forEach((message) => {
        const li = document.createElement("li");
        li.textContent = message;
        ul.appendChild(li);
      });
      this.errors.append(strong, ul);
    }

    clearErrors() {
      this.form.querySelectorAll("[aria-invalid='true']").forEach((field) => field.removeAttribute("aria-invalid"));
      if (this.errors) {
        this.errors.hidden = true;
        this.errors.innerHTML = "";
      }
    }

    async submit() {
      if (!this.validateStep(3)) return;
      this.setSubmitting(true);
      this.clearErrors();

      try {
        const response = await fetch(config.restUrl, {
          method: "POST",
          body: new FormData(this.form),
          credentials: "same-origin",
          headers: { "Accept": "application/json" }
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.ok) {
          throw new Error(data.message || config.i18n?.genericError || "Submission failed.");
        }

        this.steps.forEach((step) => {
          step.hidden = true;
          step.classList.remove("is-active");
        });
        const progress = this.form.querySelector(".sc-ei-progress");
        if (progress) progress.hidden = true;
        if (this.success) {
          this.success.hidden = false;
          const reference = this.success.querySelector(selectors.reference);
          if (reference) reference.textContent = data.reference || "";
          this.success.setAttribute("tabindex", "-1");
          this.success.focus();
        }
        this.form.reset();
      } catch (error) {
        this.showErrors([error.message || config.i18n?.genericError || "Submission failed."]);
      } finally {
        this.setSubmitting(false);
      }
    }

    setSubmitting(active) {
      if (!this.submitButton) return;
      this.submitButton.disabled = active;
      const label = this.submitButton.querySelector("span") || this.submitButton;
      label.textContent = active
        ? (config.i18n?.submitting || "Submitting…")
        : (config.i18n?.submit || "Submit Private Inquiry");
      this.form.classList.toggle("is-submitting", active);
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(selectors.form).forEach((form) => new EngagementForm(form));
  });
})();
