document.addEventListener('DOMContentLoaded', function() {
    let button = document.getElementById('form-modal-shortcode-button');
    let overlay = document.getElementById('form-modal-shortcode-overlay');
    let modal = document.getElementById('form-modal-shortcode-modal');
    let form = document.getElementById('form-modal-shortcode');
    let close = document.getElementById('modal-close');

    button.addEventListener('click', function() {
        overlay.style.display = 'block';
        modal.style.display = 'block';
    });

    // Hide modal and overlay when overlay is clicked
    overlay.addEventListener('click', function() {
        overlay.style.display = 'none';
        modal.style.display = 'none';
    });

    close.addEventListener('click', function() {
        overlay.style.display = 'none';
        modal.style.display = 'none';
    });

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        let artist_name = document.getElementById('artist-name').value;
        let title_of_work = document.getElementById('title-of-work').value;
        let instagram_handle = document.getElementById('instagram-handle').value;
        let email = document.getElementById('email').value;
        let image_file = document.getElementById('image-file').value;
        let success_message = document.getElementById('form-modal-shortcode-message');


        let xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                // Form submission successful
                success_message.classList.toggle('hidden')
                setTimeout(function() {
                    overlay.style.display = 'none';
                    modal.style.display = 'none';
                    form.reset();
                }, 3000);
                
            }
        };
        xhr.open('POST', form_modal_shortcode_ajax_object.ajax_url, true);
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.send(
            'action=form_modal_shortcode_submit' +
            '&artist_name=' + artist_name +
            '&title_of_work=' + title_of_work +
            '&instagram_handle=' + instagram_handle +
            '&email=' + email +
            '&image_file=' + image_file +
            '&_ajax_nonce=' + form_modal_shortcode_ajax_object.nonce
        );
    });
});