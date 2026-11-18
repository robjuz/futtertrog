class ScrollIntoView extends HTMLElement {
    constructor() {
        super();
        this.current = this.querySelector('.selected');
        this.scrollIntoView();

        window.addEventListener('resize', this.scrollIntoView.bind(this));

    }

    scrollIntoView() {
        const halfWindow = window.innerWidth / 2;
        if (!this.current) {
            return;
        }
        const currentElementWidth = this.current.getBoundingClientRect().width;
        if (!this.current) {
            return;
        }
        this.current.parentElement.scrollLeft += this.current.getBoundingClientRect().left - halfWindow + currentElementWidth / 2;
    }

}

class ExpandableMenu {
    constructor(nav) {
        this.nav = nav;

        this.nav.classList.add('js');
        this.createToggleButton();
        this.updateLinks();
    }

    createToggleButton() {
        if (!!this.nav.getElementsByTagName('ul')[0]) {
            this.toggleButton = document.createElement('button');
            this.toggleButton.innerHTML = this.nav.getAttribute('data-button');
            this.toggleButton.setAttribute('aria-haspopup', 'true');
            this.toggleButton.setAttribute('aria-expanded', 'false');

            this.nav.insertAdjacentElement('afterbegin', this.toggleButton);
            this.toggleButton.addEventListener('click', this.onButtonClick.bind(this));
        }
    }

    updateLinks() {
        this.nav.querySelectorAll('a').forEach(function (link) {
            let href = link.getAttribute('href');
            link.setAttribute('href', href.split('#')[0]);
        });
    }

    onButtonClick() {
        if (this.isExpanded()) {
            this.toggleButton.setAttribute('aria-expanded', 'false');
        } else {
            this.toggleButton.setAttribute('aria-expanded', 'true');
        }
    }

    isExpanded() {
        return this.toggleButton.getAttribute('aria-expanded') === 'true';
    }

}

/* Initialising instances */
if (!!document.getElementById('main-navbar')) {
    new ExpandableMenu(document.getElementById('main-navbar'));
}

if (customElements && customElements.define) {
    customElements.define('scroll-into-view', ScrollIntoView);
}

/* disable submit button on form submit */
document.addEventListener('submit', function (e) {
    if (!e.target.matches(".meal-form")) {
        const submit = e.target.querySelector("[type=\"submit\"]");
        if (submit) {
            submit.disabled = true;
        }
    }
});


async function handleMealFormSubmit(form) {
    const wrapper = form.closest('.meal')

    const button = form.querySelector("[type=\"submit\"]");
    const error = document.getElementById("action-error");
    button.disabled = true;
    error.hidden = true;
    try {
        if (!navigator.onLine) {
            throw new Error("Sie sind offline. Es wurde nichts gesendet.");
        }
        const response = await fetch(form.getAttribute("action"), {
            method: "POST", credentials: "same-origin", redirect: "error",
            headers: { "X-CSRF-TOKEN": window.Futtertrog.csrf, "X-Requested-With": "XMLHttpRequest" },
            body: new FormData(form)
        });
        if ([401, 419].includes(response.status)) {
            throw new Error("Sitzung abgelaufen. Bitte im Profil erneut anmelden und dann wiederholen.");
        }
        if (response.status === 403) {
            throw new Error("Bestellfrist abgelaufen oder Änderung nicht erlaubt.");
        }
        if (!response.ok) {
            throw new Error("Keine Bestätigung erhalten. Bitte Auswahl prüfen und erneut versuchen.");
        }
        const tmp = document.createElement("div");
        tmp.innerHTML = await response.text();
        const meal = tmp.querySelector(".meal");
        if (!meal) {
            throw new Error("Keine Bestätigung erhalten. Bitte die Übersicht prüfen.");
        }
        wrapper.innerHTML = meal.innerHTML;
        wrapper.className = meal.className;
    } catch (failure) {
        error.textContent = failure instanceof TypeError ? "Keine Serverbestätigung erhalten. Bitte die Übersicht vor einer Wiederholung prüfen." : failure.message;
        error.hidden = false;
        error.focus();
    } finally {
        button.disabled = false;
    }

}


/* are you sure modal on delete form */
var handleFormSubmit = function (e) {
    const form = e.target
    if (form.matches('.meal-form')) {
        e.preventDefault();
        return handleMealFormSubmit(form);
    }

    if (form.querySelector('[name="_method"][value="delete"]')) {
        if ((confirm(window.Futtertrog.messages.are_you_sure))) {
            e.target.submit();
        } else {
            e.preventDefault();
            e.target.querySelector('[type="submit"]').disabled = false;
        }
    }
};

document.addEventListener('submit', handleFormSubmit);

function updateOfflineStatus() {
    const status = document.getElementById("offline-status");
    if (status) {
        status.hidden = navigator.onLine;
    }
}
window.addEventListener("online", updateOfflineStatus);
window.addEventListener("offline", updateOfflineStatus);
window.addEventListener("futtertrog:update", function () {
    const dialog = document.getElementById("newVersionDialog");
    if (dialog) {
        dialog.style.display = "block";
    }
});
updateOfflineStatus();
