import './stimulus_bootstrap.js';
import './styles/app.css';

const metaUrl = document.body.dataset.metaUrl;
const refreshInterval = Number.parseInt(document.body.dataset.refreshInterval || '0', 10);
const currentBoardVersion = Number.parseInt(document.body.dataset.boardVersion || '0', 10);

if (metaUrl && refreshInterval >= 10 && currentBoardVersion > 0) {
    window.setInterval(async () => {
        try {
            const response = await fetch(metaUrl, {
                headers: {
                    'Accept': 'application/json',
                },
                cache: 'no-store',
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            if (Number.parseInt(`${data.boardVersion}`, 10) !== currentBoardVersion) {
                window.location.reload();
            }
        } catch (error) {
            console.debug('Board refresh check failed.', error);
        }
    }, refreshInterval * 1000);
}
