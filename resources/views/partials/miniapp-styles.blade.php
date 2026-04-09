<style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }
  body {
    background-color: var(--tg-theme-bg-color, #ffffff);
    color: var(--tg-theme-text-color, #000000);
    font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
    padding: 16px;
    }
    .loading-spinner {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px;
    }
    .toast-message {
    position: fixed;
    bottom: 20px;
    left: 16px;
    right: 16px;
    background: rgba(0,0,0,0.8);
    backdrop-filter: blur(8px);
    color: white;
    padding: 12px 20px;
    border-radius: 30px;
    text-align: center;
    z-index: 9999;
    font-size: 14px;
    opacity: 0;
    transition: opacity 0.2s;
    pointer-events: none;
    }
    .toast-message.show {
    opacity: 1;
    }
    </style>