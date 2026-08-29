const analyticsEndpoint = document
    .querySelector(
        'meta[name="portal-analytics-endpoint"]'
    )
    ?.getAttribute('content');

const csrfToken = document
    .querySelector(
        'meta[name="csrf-token"]'
    )
    ?.getAttribute('content');

const routeName =
    document.body?.dataset
        .portalRoute || '';

const locale =
    document.body?.dataset
        .portalLocale || '';

const enabled = Boolean(
    analyticsEndpoint
    && csrfToken
    && navigator.doNotTrack !== '1'
);

const clean = (
    value,
    maxLength = 255
) => {
    const normalized =
        String(value ?? '').trim();

    return normalized
        ? normalized.slice(
            0,
            maxLength
        )
        : null;
};

const track = async (
    eventName,
    payload = {}
) => {
    if (!enabled) {
        return;
    }

    const body = {
        event_name: clean(
            eventName,
            80
        ),
        category: clean(
            payload.category,
            80
        ),
        label: clean(
            payload.label,
            255
        ),
        value:
            Number.isFinite(
                Number(payload.value)
            )
                ? Number(
                    payload.value
                )
                : null,
        route_name:
            clean(
                routeName,
                160
            ),
        path:
            clean(
                window.location.pathname,
                500
            ),
        locale:
            clean(
                locale,
                5
            ),
        metadata:
            payload.metadata
            && typeof payload.metadata
                === 'object'
                ? payload.metadata
                : null,
    };

    if (!body.event_name) {
        return;
    }

    try {
        await fetch(
            analyticsEndpoint,
            {
                method: 'POST',
                credentials:
                    'same-origin',
                keepalive: true,
                headers: {
                    'Content-Type':
                        'application/json',
                    'Accept':
                        'application/json',
                    'X-CSRF-TOKEN':
                        csrfToken,
                },
                body: JSON.stringify(
                    body
                ),
            }
        );
    } catch {
        // Analytics must never interfere
        // with the portal UX.
    }
};

const labAction = (target) => {
    if (
        ! routeName.startsWith(
            'lab.'
        )
        || !(target instanceof Element)
    ) {
        return null;
    }

    const element =
        target.closest(
            '[data-action],'
            + '[data-mpo-action],'
            + '[data-wiggle-action]'
        );

    if (!element) {
        return null;
    }

    return (
        element.dataset.action
        || element.dataset.mpoAction
        || element.dataset.wiggleAction
        || null
    );
};

document.addEventListener(
    'click',
    (event) => {
        const target =
            event.target instanceof Element
                ? event.target
                : null;

        const explicit =
            target?.closest(
                '[data-analytics-event]'
            );

        if (explicit) {
            track(
                explicit.dataset
                    .analyticsEvent,
                {
                    category:
                        explicit.dataset
                            .analyticsCategory,
                    label:
                        explicit.dataset
                            .analyticsLabel,
                    value:
                        explicit.dataset
                            .analyticsValue,
                }
            );

            return;
        }

        const action =
            labAction(target);

        if (action) {
            track(
                'lab_action',
                {
                    category:
                        routeName,
                    label: action,
                }
            );
        }
    },
    { passive: true }
);

document.addEventListener(
    'submit',
    (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        let action;

        try {
            action = new URL(
                form.action,
                window.location.origin
            ).pathname;
        } catch {
            return;
        }

        if (
            /\/cart\/items\/?$/.test(
                action
            )
            && form.method
                .toLowerCase()
                === 'post'
        ) {
            track(
                'add_to_cart',
                {
                    category:
                        'commerce',
                }
            );

            return;
        }

        if (
            /\/newsletter\/subscribe\/?$/.test(
                action
            )
            && form.method
                .toLowerCase()
                === 'post'
        ) {
            track(
                'newsletter_subscribe',
                {
                    category:
                        'newsletter',
                }
            );

            return;
        }

        if (
            /\/checkout\/?$/.test(
                action
            )
            && form.method
                .toLowerCase()
                === 'post'
        ) {
            track(
                'checkout_submit',
                {
                    category:
                        'commerce',
                }
            );
        }
    }
);

document.addEventListener(
    'change',
    (event) => {
        const target =
            event.target instanceof Element
                ? event.target
                : null;

        const galleryMode =
            target?.closest(
                '[data-gallery-mode]'
            );

        if (galleryMode) {
            track(
                'gallery_mode',
                {
                    category:
                        'gallery',
                    label:
                        galleryMode.value,
                }
            );

            return;
        }

        const archiveMode =
            target?.closest(
                '[data-archive-mode]'
            );

        if (archiveMode) {
            track(
                'archive_view_mode',
                {
                    category:
                        'archive',
                    label:
                        archiveMode.value,
                }
            );
        }
    }
);

window.PortalAnalytics = {
    track,
};
