/**
 * Prev/next controls for a server-rendered carousel track.
 *
 * Mounted into existing markup by src/index.js — the track and its
 * cards are plain HTML, so rows work with and without JavaScript.
 * Controls show only when the track actually overflows; a
 * ResizeObserver re-checks after late image loads, font shifts and
 * orientation changes, with resize + load fallbacks.
 */
import { useEffect, useState } from 'react';
import { __ } from '@wordpress/i18n';

export default function CarouselNav({ track }) {
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        if (!track) {
            return undefined;
        }
        const check = () => {
            setVisible(track.scrollWidth > track.clientWidth + 2);
        };
        check();
        window.addEventListener('resize', check);
        window.addEventListener('load', check);
        const settled = setTimeout(check, 600);
        let observer;
        if (typeof ResizeObserver !== 'undefined') {
            observer = new ResizeObserver(check);
            observer.observe(track);
        }
        return () => {
            window.removeEventListener('resize', check);
            window.removeEventListener('load', check);
            clearTimeout(settled);
            if (observer) {
                observer.disconnect();
            }
        };
    }, [track]);

    if (!visible) {
        return null;
    }

    const scroll = (direction) => {
        track.scrollBy({
            left: direction * track.clientWidth * 0.8,
            behavior: 'smooth',
        });
    };

    return (
        <div className="ssc-carousel-nav">
            <button
                type="button"
                className="ssc-carousel-prev"
                aria-label={__('Previous', 'staysuite-companion')}
                onClick={() => scroll(-1)}
            >
                ‹
            </button>
            <button
                type="button"
                className="ssc-carousel-next"
                aria-label={__('Next', 'staysuite-companion')}
                onClick={() => scroll(1)}
            >
                ›
            </button>
        </div>
    );
}
