(function() {
    var accountCreation = document.querySelector('#accountCreation');
    var username = document.querySelector('#username');
    var password = document.querySelector('#password');
    var email = document.querySelector('#email');
    var create = document.querySelector('#create');
    var statusLine = document.querySelector('#statusLine');

    username.focus();
    
    function createAccount() {
        formData = new FormData();
        formData.append('username', username.value);
        formData.append('password', password.value);
        formData.append('email', email.value);
        promise = fetch('account', {
            method: 'POST',
            body: formData,
        }).then(response => {
            if (response.ok) {
                accountCreation.textContent = 'Please check your e-mail and ' +
                    'follow the link therein to validate your account. ' +
                    'Check the spam folder if you don\'t see the message ' +
                    'within a few minutes.';
            } else {
                response.json().then(function(obj) {
                    str = response.error || 'Unknown error.';
                    statusLine.textContent = str;
                })['catch'](function(e) {
                    console.log(e);
                    str = 'Unknown error.';
                    statusLine.textContent = str;
                });

                setTimeout(function() {
                    if (statusLine.textContent === str) {
                        statusLine.textContent = '';
                    }

                }, 5000);
            }
        });

    }

    create.addEventListener('click', createAccount);

    function keyDown(e) {
        if (e.keyCode === 13) {
            createAccount();
        }
    }

    username.addEventListener('keydown', keyDown);
    password.addEventListener('keydown', keyDown);
    email.addEventListener('keydown', keyDown);
}());