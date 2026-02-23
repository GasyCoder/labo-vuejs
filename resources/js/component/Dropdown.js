import { createPopper } from '@popperjs/core';
import { isRTL } from "../function";

let dropdownIsInitialized = false;

const getNextSibling = (element, selector) => {
    let sibling = element.nextElementSibling;

    while (sibling) {
        if (sibling.matches(selector)) {
            return sibling;
        }

        sibling = sibling.nextElementSibling;
    }

    return null;
};

const closeAllDropdowns = (except = null) => {
    document.querySelectorAll('.dropdown-toggle.show').forEach((item) => {
        if (item !== except) {
            item.classList.remove('show');
        }
    });
};

export default function Dropdown() {
    if (dropdownIsInitialized) {
        return;
    }

    dropdownIsInitialized = true;

    document.addEventListener('click', (event) => {
        const dropdownToggle = event.target.closest('.dropdown-toggle');
        const dropdownMenu = event.target.closest('.dropdown-menu');
        const clickable = event.target.closest('.clickable');
        const choiceItem = event.target.classList.contains('choices__item');

        if (dropdownToggle) {
            event.preventDefault();

            const offset = dropdownToggle.dataset.offset
                ? [parseInt(dropdownToggle.dataset.offset.split(',')[0], 10), parseInt(dropdownToggle.dataset.offset.split(',')[1], 10)]
                : [0, 0];
            const rtlOffset = dropdownToggle.dataset.rtlOffset
                ? [parseInt(dropdownToggle.dataset.rtlOffset.split(',')[0], 10), parseInt(dropdownToggle.dataset.rtlOffset.split(',')[1], 10)]
                : offset;
            const placement = dropdownToggle.dataset.placement || 'bottom-start';
            const rtlPlacement = dropdownToggle.dataset.rtlPlacement || 'bottom-end';
            const menu = getNextSibling(dropdownToggle, '.dropdown-menu');

            if (!menu) {
                return;
            }

            createPopper(dropdownToggle, menu, {
                placement: isRTL() ? rtlPlacement : placement,
                modifiers: [
                    {
                        name: 'offset',
                        options: {
                            offset: isRTL() ? rtlOffset : offset,
                        },
                    },
                    {
                        name: 'preventOverflow',
                        options: {
                            padding: 8,
                            altAxis: true,
                            boundary: '#pagecontent',
                        },
                    },
                ],
            });

            const isNowOpen = !dropdownToggle.classList.contains('show');
            closeAllDropdowns(dropdownToggle);
            dropdownToggle.classList.toggle('show', isNowOpen);

            return;
        }

        if (dropdownMenu || clickable || choiceItem) {
            return;
        }

        closeAllDropdowns();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAllDropdowns();
        }
    });
}
