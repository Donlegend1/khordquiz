import { useEffect, useRef } from 'react';

// dangerouslySetInnerHTML never executes <script> tags (browsers ignore
// script elements created via innerHTML), so embed codes that load a player
// via <script src="..."> — Wistia, Vimeo's JS embed, etc. — need their
// script tags recreated as real DOM nodes to actually run.
export default function VideoEmbed({ html, className }) {
    const containerRef = useRef(null);

    useEffect(() => {
        const container = containerRef.current;
        if (!container) return;

        container.innerHTML = html;

        container.querySelectorAll('script').forEach((oldScript) => {
            const newScript = document.createElement('script');
            for (const { name, value } of oldScript.attributes) {
                newScript.setAttribute(name, value);
            }
            newScript.textContent = oldScript.textContent;
            oldScript.replaceWith(newScript);
        });
    }, [html]);

    return <div ref={containerRef} className={className} />;
}

