/**
 * TriNova Client Portal UI Helper Script
 */
document.addEventListener('DOMContentLoaded', () => {
    // Session timeout warning helper (optional client-side prompt)
    let inactivityTimer;
    const resetTimer = () => {
        clearTimeout(inactivityTimer);
        // 14 minutes warning prompt before 15 min server session timeout
        inactivityTimer = setTimeout(() => {
            console.log('Session approaching 15-minute inactivity limit.');
        }, 840000);
    };

    window.addEventListener('mousemove', resetTimer);
    window.addEventListener('keydown', resetTimer);
    resetTimer();
});
