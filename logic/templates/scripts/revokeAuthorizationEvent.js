(function() {
    var selector = '#authorizations';
    var authorizations = document.querySelector(selector);
    authorizations.addEventListener('click', function(e) {
        var promise;
        var formData;
        var str;
        var dg = 'data-gai';
        var target = e.target;
        var parent = target.parentElement;
        var gai = parent.getAttribute(dg);
        var statusLine = document.querySelector('#statusLine');
        if (target.classList.contains('revokeAuthorizationButton')) {
            formData = new FormData();
            formData.append('globalAuthorizationId', gai);
            promise = fetch('unauthorize', {
                method: 'POST',
                body: formData,
                credentials: 'include',
            }).then(response => {
                if (response.ok) {
                    target.parentElement.style.opacity = 0;
                    setTimeout(function() {
                        parent.parentElement.removeChild(parent);
                        if (!authorizations.querySelector('.authorization')) {
                            authorizations.textContent = 'No authorizations.';
                        }
                    }, 500);
                } else {
                    try {
                        response.json().then(function(obj) {
                            var unknown = 'Unknown error';
                            var status = response.error || unknown;
                            statusLine.textContent = status;
                            str = status;
                        });
                    } catch (e) {
                        console.log(e);
                    }
                }


                setTimeout(function() {
                    if (statusLine.textContent === str) {
                        statusLine.textContent = '';
                    }

                }, 5000);
            });
        }
    });
}());