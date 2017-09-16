(function() {
    var nameOrId = document.querySelector('#nameOrId');
    var password = document.querySelector('#password');
    var loginButton = document.querySelector('#login');
    
    nameOrId.focus();
    
    function login() {
        var form = document.createElement('form');
        var submit = document.createElement('input');
        var subClone = true;
        var nameElem;
        var idElem;
        var nameOrIdValue = nameOrId.value;
        var possibleId = Number(nameOrIdValue);

        form.style.display = 'none';
        form.setAttribute('method', 'POST');
        form.setAttribute('action', 'login');
        if (Number.isNaN(possibleId) ||
            possibleId < 0 ||
            possibleId % 1)
        {
            nameElem = document.createElement('input');
            nameElem.setAttribute('name', 'name');
            nameElem.value = nameOrIdValue;
            form.appendChild(nameElem);
        } else {
            idElem = document.createElement('input');
            idElem.setAttribute('name', 'id');
            idElem.value = possibleId;
            form.appendChild(idElem);
        }

        form.appendChild(password.cloneNode(subClone));
        submit.type = 'submit';
        form.appendChild(submit);
        document.body.appendChild(form);
        submit.click();
    }

    loginButton.addEventListener('click', login);

    function keyDown(e) {
        if (e.keyCode === 13) {
            login();
        }
    }

    nameOrId.addEventListener('keydown', keyDown);
    password.addEventListener('keydown', keyDown);
}());