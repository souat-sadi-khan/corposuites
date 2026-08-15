const Lobibox = {
    notify: function(type, options) {
        const defaults = {
            size: 'normal',
            rounded: false,
            position: 'bottom right',
            icon: '',
            msg: '',
            delay: 4000
        };

        const settings = Object.assign({}, defaults, options);
        const positions = settings.position.split(' ');

        let wrapperClass = `lobibox-notify-wrapper ${positions[0]} ${positions[1]}`;
        let wrapper = document.querySelector(`.${positions[0]}.${positions[1]}.lobibox-notify-wrapper`);

        if (!wrapper) {
            wrapper = document.createElement('div');
            wrapper.className = wrapperClass;
            document.body.appendChild(wrapper);
        }

        const notifyBox = document.createElement('div');
        notifyBox.className = `lobibox-notify ${type} ${settings.size}`;
        if (settings.rounded) notifyBox.classList.add('rounded');

        let iconHtml = settings.icon ? `<div class="lobibox-icon"><i class="${settings.icon}"></i></div>` : '';

        notifyBox.innerHTML = `
            ${iconHtml}
            <div class="lobibox-msg">${settings.msg}</div>
            <span class="lobibox-close">&times;</span>
        `;

        wrapper.appendChild(notifyBox);

        notifyBox.querySelector('.lobibox-close').addEventListener('click', () => {
            removeToast(notifyBox);
        });

        setTimeout(() => {
            removeToast(notifyBox);
        }, settings.delay);

        function removeToast(box) {
            box.style.animation = 'lobiboxFadeOut 0.3s ease forwards';
            box.addEventListener('animationend', () => {
                box.remove();
                if (wrapper.children.length === 0) {
                    wrapper.remove();
                }
            });
        }
    }
};

// Usage example:

// Warning
// Lobibox.notify('warning', {
//     size: 'mini',
//     rounded: true,
//     position: 'bottom right',
//     icon: 'ri-error-warning-line',
//     msg: 'Access Key is required.'
// });

// // Success
// Lobibox.notify('success', {
//     size: 'mini',
//     rounded: true,
//     position: 'bottom right',
//     icon: 'ri-checkbox-circle-line',
//     msg: 'Installation completed successfully!'
// });

// // Error
// Lobibox.notify('error', {
//     size: 'mini',
//     position: 'top right',
//     icon: 'ri-close-circle-line',
//     msg: 'Database connection failed.'
// });

// // Info
// Lobibox.notify('info', {
//     size: 'normal',
//     rounded: false,
//     position: 'bottom right',
//     icon: 'ri-information-line',
//     msg: 'Please check your system requirements.'
// });
