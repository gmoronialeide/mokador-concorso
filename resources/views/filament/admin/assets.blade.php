<style>
    .fi-resource-plays .fi-ta-table {
        min-width: 1100px;
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
            (root || document).querySelectorAll('.fi-ta-content').forEach(bindWheelHorizontal);
        }

        document.addEventListener('DOMContentLoaded', function () { scan(); });
        document.addEventListener('livewire:navigated', function () { scan(); });

        new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
                var added = mutations[i].addedNodes;
                for (var j = 0; j < added.length; j++) {
                    var n = added[j];
                    if (n.nodeType !== 1) continue;
                    if (n.matches && n.matches('.fi-ta-content')) bindWheelHorizontal(n);
                    if (n.querySelectorAll) n.querySelectorAll('.fi-ta-content').forEach(bindWheelHorizontal);
                }
            }
        }).observe(document.body, { childList: true, subtree: true });
    })();
</script>
