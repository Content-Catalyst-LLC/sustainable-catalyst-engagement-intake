(() => {
  "use strict";

  const quarantineControls = document.querySelector("[data-sc-ei-bulk-controls]");
  if (quarantineControls) {
    const operation = quarantineControls.querySelector("[data-sc-ei-bulk-operation]");
    const retention = quarantineControls.querySelector("[data-sc-ei-bulk-retention]");
    const confirmation = quarantineControls.querySelector("[data-sc-ei-bulk-confirmation]");
    const form = quarantineControls.closest("form");

    const syncQuarantine = () => {
      const value = operation?.value || "";
      const needsRetention = value === "set_retention";
      const needsConfirmation = value === "reject_delete";

      if (retention) {
        retention.hidden = !needsRetention;
        retention.required = needsRetention;
      }
      if (confirmation) {
        confirmation.hidden = !needsConfirmation;
        confirmation.required = needsConfirmation;
        if (!needsConfirmation) confirmation.value = "";
      }
    };

    operation?.addEventListener("change", syncQuarantine);
    syncQuarantine();

    form?.addEventListener("submit", (event) => {
      const selected = form.querySelectorAll("input[name='attachment_ids[]']:checked");
      if (!selected.length) {
        event.preventDefault();
        window.alert("Select at least one private document.");
        return;
      }
      if (!operation?.value) {
        event.preventDefault();
        window.alert("Choose a bulk document action.");
        return;
      }
      if (operation.value === "reject_delete") {
        if ((confirmation?.value || "") !== "REJECT SELECTED") {
          event.preventDefault();
          window.alert("Type REJECT SELECTED exactly before deleting selected physical files.");
          return;
        }
        if (!window.confirm("Reject the selected records and permanently delete their physical private files?")) {
          event.preventDefault();
        }
      }
    });
  }

  const reviewControls = document.querySelector("[data-sc-ei-review-bulk]");
  if (reviewControls) {
    const operation = reviewControls.querySelector("[data-sc-ei-review-bulk-operation]");
    const assignee = reviewControls.querySelector("[data-sc-ei-review-bulk-assignee]");
    const priority = reviewControls.querySelector("[data-sc-ei-review-bulk-priority]");
    const stage = reviewControls.querySelector("[data-sc-ei-review-bulk-stage]");
    const due = reviewControls.querySelector("[data-sc-ei-review-bulk-due]");
    const reason = reviewControls.querySelector("[data-sc-ei-review-bulk-reason]");
    const form = reviewControls.closest("form");

    const syncReview = () => {
      const value = operation?.value || "";
      const states = {
        assignee: value === "assign",
        priority: value === "priority",
        stage: value === "stage",
        due: value === "due",
        reason: value === "escalate" || value === "resolve_escalation"
      };

      [[assignee, states.assignee], [priority, states.priority], [stage, states.stage], [due, states.due], [reason, states.reason]]
        .forEach(([field, visible]) => {
          if (!field) return;
          field.hidden = !visible;
          field.required = visible && !(field === reason && value === "resolve_escalation");
        });

      if (reason) {
        reason.placeholder = value === "escalate"
          ? "Required escalation reason"
          : "Optional resolution note";
      }
    };

    operation?.addEventListener("change", syncReview);
    syncReview();

    form?.addEventListener("submit", (event) => {
      const selected = form.querySelectorAll("input[name='inquiry_ids[]']:checked");
      if (!selected.length) {
        event.preventDefault();
        window.alert("Select at least one inquiry.");
        return;
      }
      if (!operation?.value) {
        event.preventDefault();
        window.alert("Choose a bulk review action.");
        return;
      }
      if (operation.value === "stage" && stage?.value === "completed") {
        if (!window.confirm("Mark the selected reviews completed only when each already satisfies its checklist, fit-decision, next-step, and rationale requirements?")) {
          event.preventDefault();
        }
      }
    });
  }

  const reviewForm = document.querySelector("[data-sc-ei-review-form]");
  if (reviewForm) {
    let dirty = false;
    const checklist = reviewForm.querySelectorAll(".sc-ei-review-checklist input[type='checkbox']");
    const progress = reviewForm.querySelector(".sc-ei-review-checklist legend span");

    const updateProgress = () => {
      if (!progress || !checklist.length) return;
      const completed = [...checklist].filter((item) => item.checked).length;
      progress.textContent = `${Math.round((completed / checklist.length) * 100)}%`;
    };

    reviewForm.addEventListener("input", () => {
      dirty = true;
      updateProgress();
    });
    reviewForm.addEventListener("change", () => {
      dirty = true;
      updateProgress();
    });
    reviewForm.addEventListener("submit", () => {
      dirty = false;
    });
    window.addEventListener("beforeunload", (event) => {
      if (!dirty) return;
      event.preventDefault();
      event.returnValue = "";
    });
    updateProgress();
  }

  const composeForm = document.querySelector("[data-sc-ei-compose-form]");
  if (composeForm) {
    const templateSelect = composeForm.querySelector("[data-sc-ei-template-select]");
    const templateVersion = composeForm.querySelector("[data-sc-ei-template-version]");
    const communicationType = composeForm.querySelector("[data-sc-ei-communication-type]");
    const subject = composeForm.querySelector("[data-sc-ei-subject]");
    const body = composeForm.querySelector("[data-sc-ei-body]");
    const dataNode = document.getElementById("sc-ei-template-data");
    let templates = {};
    let dirty = false;

    try {
      templates = dataNode ? JSON.parse(dataNode.textContent || "{}") : {};
    } catch (error) {
      templates = {};
    }

    templateSelect?.addEventListener("change", () => {
      const template = templates[templateSelect.value];
      if (!template) return;

      const hasContent = Boolean((subject?.value || "").trim() || (body?.value || "").trim());
      if (hasContent && !window.confirm("Replace the current subject and message with the selected rendered template?")) {
        templateSelect.value = "";
        return;
      }

      if (subject) subject.value = template.subject || "";
      if (body) body.value = template.body || "";
      if (communicationType) communicationType.value = template.type || "general_response";
      if (templateVersion) templateVersion.value = String(template.version || 0);
      dirty = true;
    });

    composeForm.addEventListener("input", () => {
      dirty = true;
    });
    composeForm.addEventListener("change", () => {
      dirty = true;
    });
    composeForm.addEventListener("submit", () => {
      dirty = false;
    });
    window.addEventListener("beforeunload", (event) => {
      if (!dirty) return;
      event.preventDefault();
      event.returnValue = "";
    });
  }

  const doNotEmail = document.querySelector("[data-sc-ei-do-not-email]");
  if (doNotEmail) {
    const form = doNotEmail.closest("form");
    const reason = form?.querySelector("textarea[name='do_not_email_reason']");

    const syncSuppression = () => {
      if (!reason) return;
      reason.required = doNotEmail.checked;
      reason.setAttribute("aria-required", doNotEmail.checked ? "true" : "false");
    };

    doNotEmail.addEventListener("change", syncSuppression);
    syncSuppression();
  }

  const holdScope = document.querySelector("[data-sc-ei-hold-scope]");
  if (holdScope) {
    const attachmentField = document.querySelector("[data-sc-ei-attachment-hold-field]");
    const attachmentInput = attachmentField?.querySelector("input[name='attachment_id']");
    const syncHoldScope = () => {
      const needsAttachment = holdScope.value === "attachment";
      if (attachmentField) attachmentField.hidden = !needsAttachment;
      if (attachmentInput) {
        attachmentInput.required = needsAttachment;
        if (!needsAttachment) attachmentInput.value = "";
      }
    };
    holdScope.addEventListener("change", syncHoldScope);
    syncHoldScope();
  }

  document.querySelectorAll(".sc-ei-execute-form").forEach((form) => {
    form.addEventListener("submit", (event) => {
      const input = form.querySelector("input[name='confirm_execute']");
      const actionId = form.querySelector("input[name='action_id']")?.value || "";
      const expected = `EXECUTE ${actionId}`;
      if (!input || input.value.trim().toUpperCase() !== expected) {
        event.preventDefault();
        window.alert(`Type ${expected} exactly before executing this irreversible action.`);
        input?.focus();
        return;
      }
      if (!window.confirm("Execute this approved privacy or retention action now? This may irreversibly erase personal data or delete a private file.")) {
        event.preventDefault();
      }
    });
  });

})();
