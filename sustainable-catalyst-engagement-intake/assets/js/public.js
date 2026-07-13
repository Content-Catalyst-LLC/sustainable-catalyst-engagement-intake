(() => {
  "use strict";

  const config = window.scEiPublic || {};
  const routeGuidance = config.routeGuidance || {};
  const pricingGuidance = config.pricingGuidance || {};

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
      this.suggestTimezone();
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
      if (!this.validate(this.currentValidationScope?.() || this.form)) return;

      this.setSubmitting(true);
      this.clearErrors();
      emit("submissionStarted", this.eventDetail());

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

        this.onSuccess(data);
        emit("submissionSuccess", this.eventDetail({
          reference: data.reference || "",
          conversionRoute: data.conversion_route || "",
          schedulingStatus: data.scheduling_status || "not_requested"
        }));
      } catch (error) {
        this.showErrors([error.message || config.i18n?.genericError || "Submission failed."]);
        emit("submissionError", this.eventDetail({ message: error.message || "Submission failed." }));
      } finally {
        this.setSubmitting(false);
      }
    }

    onSuccess(data) {
      const reference = this.success?.querySelector("[data-sc-ei-reference]");
      if (reference) reference.textContent = data.reference || "";
      if (this.success) {
        this.success.hidden = false;
        this.success.setAttribute("tabindex", "-1");
        this.success.focus();
      }
      this.form.reset();
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
        if (["privacy_consent", "authorization_consent", "follow_up_consent", "calendar_invite_consent", "company_website"].includes(field.name)) return;

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
})();
