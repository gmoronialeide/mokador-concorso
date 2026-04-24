<style>
    .fi-resource-plays .fi-ta-table {
        min-width: 1100px;
    }

    .fi-resource-plays .fi-ta-content-ctn {
        cursor: grab;
    }

    .fi-resource-plays .fi-ta-content-ctn.is-panning {
        cursor: grabbing;
        user-select: none;
    }
</style>
<script>
    (function () {
        function bindWheelHorizontal(el) {
            if (el.dataset.wheelHorizontal) return;
            el.dataset.wheelHorizontal = '1';
            el.addEventListener('wheel', function (e) {
                if (!e.deltaY || e.shiftKey || e.ctrlKey) return;
                if (el.scrollWidth <= el.clientWidth) return;
                e.preventDefault();
                el.scrollLeft += e.deltaY;
            }, { passive: false });
        }

        function scan(root) {
            (root || document).querySelectorAll('.fi-ta-content-ctn').forEach(bindWheelHorizontal);
        }

        function startObserver() {
            new MutationObserver(function (mutations) {
                for (var i = 0; i < mutations.length; i++) {
                    var added = mutations[i].addedNodes;
                    for (var j = 0; j < added.length; j++) {
                        var n = added[j];
                        if (n.nodeType !== 1) continue;
                        if (n.matches && n.matches('.fi-ta-content-ctn')) bindWheelHorizontal(n);
                        if (n.querySelectorAll) n.querySelectorAll('.fi-ta-content-ctn').forEach(bindWheelHorizontal);
                    }
                }
            }).observe(document.body, { childList: true, subtree: true });
        }

        document.addEventListener('DOMContentLoaded', function () { scan(); startObserver(); });
        document.addEventListener('livewire:navigated', function () { scan(); });

        if (document.readyState !== 'loading' && document.body) {
            scan();
            startObserver();
        }
    })();
</script>
<script>
    (function () {
        var DRAG_THRESHOLD = 5;

        function bindDragPan(el) {
            if (el.dataset.dragPan) return;
            el.dataset.dragPan = '1';

            var startX = 0;
            var startScroll = 0;
            var pointerId = null;
            var dragging = false;
            var armed = false;

            el.addEventListener('pointerdown', function (e) {
                if (e.shiftKey) return;
                if (e.pointerType === 'mouse' && e.button !== 0) return;
                if (e.target.closest('button, a, input, label, select, textarea, [role="button"], [data-no-drag]')) return;

                startX = e.clientX;
                startScroll = el.scrollLeft;
                pointerId = e.pointerId;
                dragging = false;
                armed = true;
            });

            el.addEventListener('pointermove', function (e) {
                if (!armed || e.pointerId !== pointerId) return;
                var dx = e.clientX - startX;
                if (!dragging && Math.abs(dx) > DRAG_THRESHOLD) {
                    dragging = true;
                    try { el.setPointerCapture(pointerId); } catch (_) {}
                    el.classList.add('is-panning');
                }
                if (dragging) {
                    el.scrollLeft = startScroll - dx;
                }
            });

            function endDrag(e) {
                if (e.pointerId !== pointerId) return;
                if (dragging) {
                    el.classList.remove('is-panning');
                    try { el.releasePointerCapture(pointerId); } catch (_) {}
                    var suppress = function (ev) {
                        ev.preventDefault();
                        ev.stopPropagation();
                        window.removeEventListener('click', suppress, true);
                    };
                    window.addEventListener('click', suppress, true);
                    setTimeout(function () {
                        window.removeEventListener('click', suppress, true);
                    }, 0);
                }
                armed = false;
                dragging = false;
                pointerId = null;
            }

            el.addEventListener('pointerup', endDrag);
            el.addEventListener('pointercancel', endDrag);
        }

        function scan(root) {
            (root || document).querySelectorAll('.fi-resource-plays .fi-ta-content-ctn').forEach(bindDragPan);
        }

        function startObserver() {
            new MutationObserver(function (mutations) {
                for (var i = 0; i < mutations.length; i++) {
                    var added = mutations[i].addedNodes;
                    for (var j = 0; j < added.length; j++) {
                        var n = added[j];
                        if (n.nodeType !== 1) continue;
                        if (n.matches && n.matches('.fi-resource-plays .fi-ta-content-ctn')) bindDragPan(n);
                        if (n.querySelectorAll) n.querySelectorAll('.fi-resource-plays .fi-ta-content-ctn').forEach(bindDragPan);
                    }
                }
            }).observe(document.body, { childList: true, subtree: true });
        }

        document.addEventListener('DOMContentLoaded', function () { scan(); startObserver(); });
        document.addEventListener('livewire:navigated', function () { scan(); });

        if (document.readyState !== 'loading' && document.body) {
            scan();
            startObserver();
        }
    })();
</script>
