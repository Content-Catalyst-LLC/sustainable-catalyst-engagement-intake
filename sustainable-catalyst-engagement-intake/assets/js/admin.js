(() => {
  "use strict";

  const controls = document.querySelector("[data-sc-ei-bulk-controls]");
  if (!controls) return;

  const operation = controls.querySelector("[data-sc-ei-bulk-operation]");
  const retention = controls.querySelector("[data-sc-ei-bulk-retention]");
  const confirmation = controls.querySelector("[data-sc-ei-bulk-confirmation]");
  const form = controls.closest("form");

  const sync = () => {
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

  operation?.addEventListener("change", sync);
  sync();

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
})();
