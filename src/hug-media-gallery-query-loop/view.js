import PhotoSwipeLightbox from 'photoswipe/lightbox';
import PhotoSwipe from 'photoswipe';

/**
 * Recursive Split Algorithm for the Fancy Layout
 */
function calculateFancyLayout(items, x, y, width, height) {
    const count = items.length;
    let results = [];

    if (count === 1) {
        results.push({ ...items[0], x, y, width, height });
        return results;
    }

    const mid = Math.ceil(count / 2);
    const leftPart = items.slice(0, mid);
    const rightPart = items.slice(mid);

    let x1, y1, w1, h1, x2, y2, w2, h2;
    const ratio = leftPart.length / count;
    const remainingRatio = rightPart.length / count;

    if (width >= height) {
        w1 = width * ratio;
        w2 = width * remainingRatio;
        x1 = x;
        y1 = y;
        h1 = height;
        x2 = x + w1;
        y2 = y;
        h2 = height;
    } else {
        h1 = height * ratio;
        h2 = height * remainingRatio;
        x1 = x;
        y1 = y;
        w1 = width;
        x2 = x;
        y2 = y + h1;
        w2 = width;
    }

    results = results.concat(calculateFancyLayout(leftPart, x1, y1, w1, h1));
    results = results.concat(calculateFancyLayout(rightPart, x2, y2, w2, h2));
    return results;
}

/**
 * Renders the Fancy Layout items into the DOM
 */
function renderFancyGallery(container) {
    const wrapper = container.querySelector('.hug-media-query-loop-wrapper');
    if (!wrapper) return;

    const images = JSON.parse(wrapper.dataset.images || '[]');
    const settings = JSON.parse(wrapper.dataset.settings || '{}');
    
    if (!images.length) {
        wrapper.innerHTML = '<p>Gallery data is empty.</p>';
        return;
    }

    const layout = calculateFancyLayout(images, 0, 0, container.clientWidth, settings.maxHeight);
    
    wrapper.innerHTML = '';
    const totalHeight = layout.reduce((max, item) => Math.max(max, item.y + item.height), 0);
    wrapper.style.height = `${totalHeight}px`;

    layout.forEach(item => {
        const div = document.createElement('div');
        div.className = 'gallery-item-wrapper hug-media-item';
        div.style.cssText = `top:${item.y}px; left:${item.x}px; width:${item.width}px; height:${item.height}px; padding:${settings.padding}px;`;

        let content = '';
        if (settings.lightbox) {
            // UPDATED: Added data-pswp-project-url attribute here
            content += `<a href="${item.full_url}" 
                data-pswp-width="${Math.round(item.original_width)}" 
                data-pswp-height="${Math.round(item.original_height)}" 
                data-pswp-project-url="${item.project_url || ''}"
                class="pswp-gallery__item fancy-link-ready" 
                data-cropped="true"
                title="${item.title}"
                style="display: block; width: 100%; height: 100%;">`;
        }

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = item.image_html;
        const img = tempDiv.querySelector('img');
        if (img) {
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'cover';
            content += img.outerHTML;
        } else {
            content += item.image_html; 
        }

        if (settings.lightbox) content += '</a>';
        if (settings.showTitles) content += `<h3>${item.title}</h3>`;

        div.innerHTML = content;
        wrapper.appendChild(div);
    });

    wrapper.dataset.processed = "true";
}

/**
 * Initializes PhotoSwipe for all layouts
 */
function initLightbox() {
    const containers = document.querySelectorAll('.hug-media-query-loop-container.is-fancy-layout');
    let allFancyProcessed = true;

    containers.forEach(container => {
        const wrapper = container.querySelector('.hug-media-query-loop-wrapper');
        if (wrapper && wrapper.dataset.images && wrapper.dataset.processed !== "true") {
            renderFancyGallery(container);
            allFancyProcessed = false;
        }
    });

    if (!allFancyProcessed) {
        setTimeout(initLightbox, 100);
        return;
    }

    document.querySelectorAll('.hug-media-query-loop-wrapper').forEach((gallery, index) => {
        let childSelector = 'a[data-pswp-width]';
        if (gallery.closest('.is-fancy-layout')) {
            childSelector = '.fancy-link-ready';
        }

        if (gallery.querySelectorAll(childSelector).length > 0) {
            const lightbox = new PhotoSwipeLightbox({
                gallery: gallery,
                children: childSelector,
                pswpModule: PhotoSwipe,
                clickToCloseNonZoomable: false,
            });

            // NEW: Register the "View Project" Button
            lightbox.on('uiRegister', function() {
                lightbox.pswp.ui.registerElement({
                    name: 'project-link',
                    appendTo: 'wrapper',
                    ariaLabel: 'View Project',
                    order: 7,
                    isButton: true,
                    html: 'View Project',
                    onClick: (event, el, pswp) => {
                        const currSlideElement = pswp.currSlide.data.element;
                        const projectUrl = currSlideElement.getAttribute('data-pswp-project-url');
                        if (projectUrl && projectUrl !== '') {
                            window.location.href = projectUrl;
                        }
                    }
                });
            });

            lightbox.init();
        }
    });
}

let resizeTimer;
function handleRotation() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
        const containers = document.querySelectorAll('.hug-media-query-loop-container.is-fancy-layout');
        containers.forEach(container => {
            const wrapper = container.querySelector('.hug-media-query-loop-wrapper');
            if (wrapper) {
                wrapper.dataset.processed = "false";
                renderFancyGallery(container);
            }
        });
        initLightbox();
    }, 250); 
}

document.addEventListener('DOMContentLoaded', () => {
    initLightbox();
    window.addEventListener('resize', handleRotation);
});