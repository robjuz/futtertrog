function ExpandableMenu(nav) {
    this.nav = nav;

    this.nav.setAttribute('class', 'js');
    this.createToggleButton();
}

ExpandableMenu.prototype.createToggleButton = function() {
    if(!!this.nav.getElementsByTagName('ul')[0]){
        this.toggleButton = document.createElement('button');
        this.toggleButton.insertAdjacentHTML('afterbegin',
            '<span aria-hidden="true">&rarr;</span>' +
            '<span class="sr-only">Menü</span>'
        );
        this.toggleButton.setAttribute('aria-haspopup', 'true');
        this.toggleButton.setAttribute('aria-expanded', 'false');

        this.nav.insertAdjacentElement('afterbegin', this.toggleButton);
        this.toggleButton.addEventListener('click', ExpandableMenu.prototype.onButtonClick.bind(this));
    }
};

ExpandableMenu.prototype.onButtonClick = function() {
    if(this.toggleButton.getAttribute('aria-expanded') === 'true') {
        this.toggleButton.setAttribute('aria-expanded', 'false');
        this.toggleButton.querySelectorAll('[aria-hidden]')[0].innerHTML = '&larr;';
    } else {
        this.toggleButton.setAttribute('aria-expanded', 'true');
        this.toggleButton.querySelectorAll('[aria-hidden]')[0].innerHTML = '&rarr;';
    }
};

/* Initialising instances */
if(!!document.getElementById('main-navbar')) {
    new ExpandableMenu(document.getElementById('main-navbar'));
}
