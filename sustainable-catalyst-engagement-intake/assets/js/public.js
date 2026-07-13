(() => {
  "use strict";

  const config = window.scEiPublic || {};
  const routeGuidance = config.routeGuidance || {};
  const pricingGuidance = config.pricingGuidance || {};
  const uploadConfig = config.uploadConfig || {};

  const emit = (name, detail = {}) => {
    window.dispatchEvent(new CustomEvent(`scEi:${name}`, { detail }));
  };

  const visible = (element) => !element.hidden && element.offsetParent !== null;
  const values = (attribute) => (attribute || "").split(",").map((item) => item.trim()).filter(Boolean);

  class BaseForm {
    constructor(form) {
      this.form = form;
      this.errors = form.querySelector("[data-sc-ei-errors]");
      this.success = form.querySelector("[data-sc-ei-success]");
      this.submitButton = form.querySelector("[data-sc-ei-submit]");
      this.container = form.closest("[data-sc-ei-hub]");
      this.variant = form.querySelector("[name='form_variant']")?.value || form.dataset.mode || "advanced";
      this.source = form.querySelector("[name='source_page']")?.value || this.container?.dataset.sourcePage || "other";
      this.entryCta = form.querySelector("[name='entry_cta']")?.value || "unspecified";
      this.isSubmitting = false;
      this.suggestTimezone();
      this.bindUploads();
      emit("formView", this.eventDetail());
    }

    eventDetail(extra = {}) {
      return {
        variant: this.variant,
        source: this.source,
        entryCta: this.entryCta,
        formId: this.form.id,
        ...extra
      };
    }

    suggestTimezone() {
      this.form.querySelectorAll("[data-sc-ei-timezone]").forEach((field) => {
        if (field.value) return;
        try {
          const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
          if (timezone) field.value = timezone;
        } catch (error) {
          // Manual entry remains available.
        }
      });
    }

    bindUploads() {
      this.form.querySelectorAll("[data-sc-ei-files]").forEach((input) => {
        input.addEventListener("change", () => this.updateUploadState(input));
        this.updateUploadState(input);
      });
    }

    updateUploadState(input) {
      const files = Array.from(input.files || []);
      const maxFiles = Number(input.dataset.maxFiles || uploadConfig.max_files || uploadConfig.maxFiles || 5);
      const maxBytes = Number(input.dataset.maxBytes || uploadConfig.max_file_bytes || uploadConfig.maxBytes || (20 * 1024 * 1024));
      const maxTotalBytes = Number(input.dataset.maxTotalBytes || uploadConfig.max_total_bytes || uploadConfig.maxTotalBytes || (100 * 1024 * 1024));
      const totalBytes = files.reduce((total, file) => total + file.size, 0);
      const allowed = values(input.dataset.allowedExtensions || (uploadConfig.allowedExtensions || []).join(","))
        .map((item) => item.toLowerCase());
      let message = "";

      if (files.length > maxFiles) {
        message = config.i18n?.fileCountError || "Too many documents are selected.";
      } else if (files.some((file) => file.size > maxBytes)) {
        message = config.i18n?.fileSizeError || "One or more documents exceed the per-file size limit.";
      } else if (totalBytes > maxTotalBytes) {
        message = config.i18n?.fileTotalError || "The combined document size exceeds the safe request limit.";
      } else if (files.some((file) => {
        const name = file.name || "";
        const extension = name.includes(".") ? name.split(".").pop().toLowerCase() : "";
        return !allowed.includes(extension);
      })) {
        message = config.i18n?.fileTypeError || "One or more selected document types are not allowed.";
      }

      input.setCustomValidity(message);

      const countField = this.form.querySelector("[data-sc-ei-document-count]");
      const bytesField = this.form.querySelector("[data-sc-ei-document-bytes]");
      if (countField) countField.value = String(files.length);
      if (bytesField) bytesField.value = String(totalBytes);

      const section = input.closest("[data-sc-ei-document-section]");
      const consent = section?.querySelector("[data-sc-ei-document-consent]");
      const requiredMark = section?.querySelector("[data-sc-ei-document-required]");
      if (consent) {
        consent.required = files.length > 0;
        if (!files.length) consent.checked = false;
      }
      if (requiredMark) requiredMark.hidden = files.length === 0;

      const summary = section?.querySelector("[data-sc-ei-file-summary]");
      if (summary) {
        summary.innerHTML = "";
        if (files.length) {
          const list = document.createElement("ul");
          files.forEach((file) => {
            const item = document.createElement("li");
            item.textContent = `${file.name} — ${this.formatBytes(file.size)}`;
            list.appendChild(item);
          });
          summary.appendChild(list);
        }
        if (message) {
          const error = document.createElement("p");
          error.className = "sc-ei-file-summary__error";
          error.textContent = message;
          summary.appendChild(error);
        }
      }

      emit("documentsSelected", this.eventDetail({
        count: files.length,
        totalBytes,
        valid: !message
      }));
    }

    formatBytes(bytes) {
      const value = Number(bytes || 0);
      if (value < 1024) return `${value} B`;
      if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`;
      return `${(value / (1024 * 1024)).toFixed(1)} MB`;
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

    validate(scope = this.form) {
      const invalid = Array.from(scope.querySelectorAll("input, select, textarea"))
        .filter((field) => !field.disabled && visible(field) && !field.checkValidity());

      if (!invalid.length) {
        this.clearErrors();
        return true;
      }

      invalid.forEach((field) => field.setAttribute("aria-invalid", "true"));
      const messages = invalid.map((field) => {
        const label = field.id
          ? this.form.querySelector(`label[for="${CSS.escape(field.id)}"]`)
          : field.closest("label");
        return label ? label.textContent.replace("*", "").trim() : field.name;
      });

      this.showErrors(messages);
      invalid[0].focus();
      emit("validationError", this.eventDetail({ fields: [...new Set(messages)] }));
      return false;
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

    setSubmitting(active) {
      if (!this.submitButton) return;
      this.submitButton.disabled = active;
      const label = this.submitButton.querySelector("span") || this.submitButton;
      label.textContent = active
        ? (config.i18n?.submitting || "Submitting…")
        : (this.variant === "compact"
          ? (config.i18n?.compactSubmit || "Submit Engagement Inquiry")
          : (config.i18n?.submit || "Submit Private Inquiry"));
      this.form.classList.toggle("is-submitting", active);
    }

    async submit() {
      if (this.isSubmitting) return;
      if (!this.validate(this.currentValidationScope?.() || this.form)) return;

      if (navigator.onLine === false) {
        this.showErrors([config.i18n?.networkOffline || "The browser is offline."]);
        return;
      }

      this.isSubmitting = true;
      this.setSubmitting(true);
      this.clearErrors();
      const statusNodes = this.form.querySelectorAll("[data-sc-ei-upload-status]");
      statusNodes.forEach((node) => {
        node.hidden = false;
        node.textContent = config.i18n?.uploadingSecurely || "Uploading and verifying securely.";
      });

      const controller = new AbortController();
      const timeout = window.setTimeout(
        () => controller.abort(),
        Number(uploadConfig.timeoutMilliseconds || 180000)
      );

      emit("submissionStarted", this.eventDetail());

      try {
        const response = await fetch(config.restUrl, {
          method: "POST",
          body: new FormData(this.form),
          credentials: "same-origin",
          cache: "no-store",
          redirect: "error",
          signal: controller.signal,
          headers: {
            "Accept": "application/json",
            "Cache-Control": "no-store"
          }
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.ok) {
          throw new Error(data.message || config.i18n?.genericError || "Submission failed.");
        }

        this.onSuccess(data);
        emit("submissionSuccess", this.eventDetail({
          reference: data.reference || "",
          requestId: data.request_id || "",
          conversionRoute: data.conversion_route || "",
          schedulingStatus: data.scheduling_status || "not_requested",
          attachmentCount: Number(data.attachment_count || 0),
          attachmentErrors: Array.isArray(data.attachment_errors) ? data.attachment_errors.length : 0
        }));
      } catch (error) {
        const message = error?.name === "AbortError"
          ? (config.i18n?.uploadTimeout || "The secure upload timed out.")
          : (error.message || config.i18n?.genericError || "Submission failed.");
        this.showErrors([message]);
        emit("submissionError", this.eventDetail({ message }));
      } finally {
        window.clearTimeout(timeout);
        statusNodes.forEach((node) => {
          node.hidden = true;
          node.textContent = "";
        });
        this.setSubmitting(false);
        this.isSubmitting = false;
      }
    }

    onSuccess(data) {
      const reference = this.success?.querySelector("[data-sc-ei-reference]");
      if (reference) reference.textContent = data.reference || "";

      const attachmentCount = Number(data.attachment_count || 0);
      const attachmentErrors = Array.isArray(data.attachment_errors) ? data.attachment_errors : [];
      const attachmentSummary = this.success?.querySelector("[data-sc-ei-attachment-summary]");
      const attachmentWarnings = this.success?.querySelector("[data-sc-ei-attachment-warnings]");

      if (attachmentSummary) {
        attachmentSummary.hidden = attachmentCount < 1;
        attachmentSummary.textContent = attachmentCount > 0
          ? `${attachmentCount} ${config.i18n?.documentsQuarantined || "document(s) placed in protected quarantine"}.`
          : "";
      }

      if (attachmentWarnings) {
        attachmentWarnings.innerHTML = "";
        attachmentWarnings.hidden = attachmentErrors.length < 1;
        if (attachmentErrors.length) {
          const strong = document.createElement("strong");
          strong.textContent = "Document upload warnings";
          const list = document.createElement("ul");
          attachmentErrors.forEach((message) => {
            const item = document.createElement("li");
            item.textContent = message;
            list.appendChild(item);
          });
          attachmentWarnings.append(strong, list);
        }
      }

      if (this.success) {
        this.success.hidden = false;
        this.success.setAttribute("tabindex", "-1");
        this.success.focus();
      }
      this.form.reset();
      this.form.querySelectorAll("[data-sc-ei-files]").forEach((input) => this.updateUploadState(input));
    }
  }

  class AdaptiveForm extends BaseForm {
    constructor(form) {
      super(form);
      this.step = 1;
      this.steps = Array.from(form.querySelectorAll("[data-sc-ei-step]"));
      this.type = form.querySelector("[data-sc-ei-type]");
      this.contactMethod = form.querySelector("[data-sc-ei-contact-method]");
      this.meetingRequest = form.querySelector("[data-sc-ei-meeting-request]");
      this.bind();
      this.applyConditions();
      this.showStep(1);
    }

    bind() {
      this.form.addEventListener("click", (event) => {
        const next = event.target.closest("[data-sc-ei-next]");
        const back = event.target.closest("[data-sc-ei-back]");

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
          event.target.matches("[data-sc-ei-type]") ||
          event.target.matches("[data-sc-ei-contact-method]") ||
          event.target.matches("[data-sc-ei-meeting-request]")
        ) {
          this.applyConditions();
        }

        if (event.target.matches("[data-sc-ei-type]")) {
          emit("routeSelected", this.eventDetail({ inquiryType: event.target.value }));
        }
      });

      this.form.addEventListener("submit", (event) => {
        if (!window.fetch || !config.restUrl) return;
        event.preventDefault();
        this.submit();
      });

      if (this.container) {
        this.container.querySelectorAll("[data-sc-ei-route]").forEach((button) => {
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

    applyConditions() {
      const type = this.type?.value || "";
      const contact = this.contactMethod?.value || "email";
      const meeting = this.meetingRequest?.value || "no";

      this.form.querySelectorAll("[data-show-for]").forEach((container) => {
        this.setConditional(container, values(container.getAttribute("data-show-for")).includes(type));
      });

      this.form.querySelectorAll("[data-contact-method-show]").forEach((container) => {
        this.setConditional(container, values(container.getAttribute("data-contact-method-show")).includes(contact));
      });

      this.form.querySelectorAll("[data-meeting-request-show]").forEach((container) => {
        this.setConditional(container, values(container.getAttribute("data-meeting-request-show")).includes(meeting));
      });

      const teamsEmail = this.form.querySelector("[name='teams_email']");
      const primaryEmail = this.form.querySelector("[name='contact_email']");
      if ((meeting === "yes" || meeting === "unsure") && teamsEmail && !teamsEmail.value && primaryEmail?.value) {
        teamsEmail.value = primaryEmail.value;
      }

      const guidance = this.form.querySelector("[data-sc-ei-route-guidance]");
      if (guidance) {
        guidance.textContent = routeGuidance[type] || "";
        guidance.hidden = !guidance.textContent;
      }
    }

    next() {
      const section = this.steps.find((item) => Number(item.getAttribute("data-sc-ei-step")) === this.step);
      if (!this.validate(section || this.form)) return;

      if (this.step === 2) {
        this.buildReview();
        emit("reviewOpened", this.eventDetail({ inquiryType: this.type?.value || "" }));
      }

      this.showStep(Math.min(this.steps.length, this.step + 1));
    }

    showStep(step) {
      this.step = step;
      this.steps.forEach((section) => {
        const active = Number(section.getAttribute("data-sc-ei-step")) === step;
        section.hidden = !active;
        section.classList.toggle("is-active", active);
      });

      const bar = this.form.querySelector("[data-sc-ei-progress-bar]");
      if (bar && this.steps.length > 1) {
        bar.style.width = `${((step - 1) / (this.steps.length - 1)) * 100}%`;
      }

      this.form.querySelectorAll("[data-sc-ei-progress-step]").forEach((item) => {
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

    currentValidationScope() {
      return this.steps.find((item) => Number(item.getAttribute("data-sc-ei-step")) === this.step) || this.form;
    }

    buildReview() {
      const list = this.form.querySelector("[data-sc-ei-review-list]");
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
        if (["privacy_consent", "authorization_consent", "follow_up_consent", "calendar_invite_consent", "document_upload_consent", "company_website"].includes(field.name)) return;

        if (field.type === "file") {
          const names = Array.from(field.files || []).map((file) => `${file.name} (${this.formatBytes(file.size)})`);
          if (names.length) this.addReviewItem(list, "Selected documents", names.join(", "));
          return;
        }

        if (field.name === "preferred_weekdays[]") {
          if (processedCheckboxGroups.has(field.name)) return;
          processedCheckboxGroups.add(field.name);
          const checked = Array.from(this.form.querySelectorAll("[name='preferred_weekdays[]']:checked"))
            .map((item) => item.closest("label")?.textContent.trim() || item.value);
          if (checked.length) this.addReviewItem(list, "Preferred weekdays", checked.join(", "));
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

    onSuccess(data) {
      this.steps.forEach((step) => {
        step.hidden = true;
        step.classList.remove("is-active");
      });
      const progress = this.form.querySelector(".sc-ei-progress");
      if (progress) progress.hidden = true;
      super.onSuccess(data);
    }
  }

  class CompactForm extends BaseForm {
    constructor(form) {
      super(form);
      this.nextStep = form.querySelector("[data-sc-ei-compact-next-step]");
      this.service = form.querySelector("[data-sc-ei-compact-service]");
      this.budget = form.querySelector("[data-sc-ei-compact-budget]");
      this.bind();
      this.applyConditions();
      this.updateGuidance();
    }

    bind() {
      this.form.addEventListener("change", (event) => {
        if (event.target.matches("[data-sc-ei-compact-next-step]")) {
          this.applyConditions();
          emit("compactNextStepSelected", this.eventDetail({ nextStep: event.target.value }));
        }

        if (
          event.target.matches("[data-sc-ei-compact-service]") ||
          event.target.matches("[data-sc-ei-compact-budget]")
        ) {
          this.updateGuidance();
        }
      });

      this.form.addEventListener("submit", (event) => {
        if (!window.fetch || !config.restUrl) return;
        event.preventDefault();
        this.submit();
      });
    }

    applyConditions() {
      const nextStep = this.nextStep?.value || "email_first";
      this.form.querySelectorAll("[data-compact-next-step-show]").forEach((container) => {
        this.setConditional(container, values(container.getAttribute("data-compact-next-step-show")).includes(nextStep));
      });

      const teamsEmail = this.form.querySelector("[name='teams_email']");
      const primaryEmail = this.form.querySelector("[name='contact_email']");
      if (nextStep === "teams_fit_call" && teamsEmail && !teamsEmail.value && primaryEmail?.value) {
        teamsEmail.value = primaryEmail.value;
      }
    }

    updateGuidance() {
      const panel = this.form.querySelector("[data-sc-ei-pricing-guidance]");
      if (!panel) return;

      const service = this.service?.value || "";
      const budget = this.budget?.value || "";
      const guidance = pricingGuidance[service] || {};
      const messages = [];

      if (guidance.default) messages.push(guidance.default);

      const lowBuildBudget = ["under_1500", "1500_5000", "5000_10000"].includes(budget);
      const lowSprintBudget = ["under_1500", "1500_5000"].includes(budget);

      if (
        (service === "knowledge_platform_build" && lowBuildBudget) ||
        (service === "strategy_architecture_sprint" && lowSprintBudget)
      ) {
        if (guidance.low_budget) messages.push(guidance.low_budget);
      }

      panel.innerHTML = "";
      messages.forEach((message) => {
        const p = document.createElement("p");
        p.textContent = message;
        panel.appendChild(p);
      });
      panel.hidden = messages.length === 0;

      if (service) {
        emit("compactServiceSelected", this.eventDetail({ service, budget, guidanceShown: messages.length > 0 }));
      }
    }

    onSuccess(data) {
      Array.from(this.form.children).forEach((element) => {
        if (element !== this.success && !element.matches?.(".sc-ei-honeypot")) {
          element.hidden = true;
        }
      });
      super.onSuccess(data);
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("[data-sc-ei-form]").forEach((form) => new AdaptiveForm(form));
    document.querySelectorAll("[data-sc-ei-compact-form]").forEach((form) => new CompactForm(form));
  });
  document.querySelectorAll(".sc-ei-portal-card--danger form").forEach((form) => {
    form.addEventListener("submit", (event) => {
      const withdrawal = form.querySelector("input[name='withdrawal_confirmation']");
      const revoke = form.querySelector("input[name='revoke_confirmation']");
      const input = withdrawal || revoke;
      if (!input) return;
      const value = input.value.trim().toUpperCase();
      const acceptable = value.startsWith("WITHDRAW ") || value.startsWith("CANCEL ") || value.startsWith("REVOKE ");
      if (!acceptable || !window.confirm("Confirm this secure sender portal action?")) {
        event.preventDefault();
        input.focus();
      }
    });
  });

})();
