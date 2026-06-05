/* icons.jsx — jeu d'icônes SVG (trait fin, 24px), style outline.
   <Icon name="calendar" /> · exporté sur window.Icon */
(function () {
  const P = {
    calendar: <><rect x="3" y="4.5" width="18" height="16" rx="2.5"/><path d="M3 9h18M8 2.5v4M16 2.5v4"/></>,
    "calendar-clock": <><path d="M21 9.5V6.5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h6M3 9h18M8 2.5v4M16 2.5v4"/><circle cx="17.5" cy="17.5" r="4"/><path d="M17.5 16v1.6l1 .9"/></>,
    grid: <><rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/></>,
    clipboard: <><rect x="5" y="4.5" width="14" height="16.5" rx="2"/><path d="M9 4.5V3.5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1M8.5 10h7M8.5 14h7M8.5 18h4"/></>,
    layers: <><path d="M12 3 3 8l9 5 9-5-9-5ZM3 13l9 5 9-5M3 18l9 5 9-5"/></>,
    settings: <><circle cx="12" cy="12" r="3.2"/><path d="M19.4 13a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-2.82 1.17V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 7 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 2.6 14H2.5a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4 7.4l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9.6 4.6h.06A1.65 1.65 0 0 0 11 3v-.5a2 2 0 0 1 4 0V3a1.65 1.65 0 0 0 1.82.6 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 21.4 9H21.5a2 2 0 0 1 0 4Z"/></>,
    "chevron-left": <path d="M15 5l-7 7 7 7"/>,
    "chevron-right": <path d="M9 5l7 7-7 7"/>,
    "chevron-down": <path d="M5 9l7 7 7-7"/>,
    "arrow-left": <path d="M19 12H5M11 18l-6-6 6-6"/>,
    plus: <path d="M12 5v14M5 12h14"/>,
    check: <path d="M5 12.5l4.5 4.5L19 6.5"/>,
    x: <path d="M6 6l12 12M18 6L6 18"/>,
    trash: <><path d="M4 6.5h16M9 6.5V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1.5M6.5 6.5l1 13a2 2 0 0 0 2 1.9h5a2 2 0 0 0 2-1.9l1-13"/></>,
    pin: <><path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></>,
    clock: <><circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3.5 2"/></>,
    file: <><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z"/><path d="M14 3v5h5M8.5 13h7M8.5 17h7"/></>,
    image: <><rect x="3.5" y="4.5" width="17" height="15" rx="2"/><circle cx="9" cy="10" r="1.8"/><path d="M5 18l5-5 4 4 3-3 3 3"/></>,
    paperclip: <path d="M21 11.5l-8.4 8.4a5 5 0 0 1-7.1-7.1l8.5-8.5a3.3 3.3 0 0 1 4.7 4.7l-8.5 8.5a1.7 1.7 0 0 1-2.4-2.4l7.8-7.8"/>,
    upload: <><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 9l5-5 5 5M12 4v12"/></>,
    "rotate": <><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></>,
    users: <><circle cx="9" cy="8" r="3.4"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M17 4.5a3.4 3.4 0 0 1 0 6.6M21.5 20a6 6 0 0 0-4-5.6"/></>,
    bell: <><path d="M18 9a6 6 0 1 0-12 0c0 5-2.5 6.5-2.5 6.5h17S18 14 18 9Z"/><path d="M10 20a2 2 0 0 0 4 0"/></>,
    pencil: <><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/><path d="M14 6l3 3"/></>,
    book: <><path d="M3 5.5A2.5 2.5 0 0 1 5.5 3H12v16H5.5A2.5 2.5 0 0 0 3 21.5ZM21 5.5A2.5 2.5 0 0 0 18.5 3H12v16h6.5a2.5 2.5 0 0 1 2.5 2.5Z"/></>,
    alert: <><circle cx="12" cy="12" r="9"/><path d="M12 7.5v5M12 16h.01"/></>,
    "check-circle": <><circle cx="12" cy="12" r="9"/><path d="M8.5 12.5l2.5 2.5 4.5-5"/></>,
    menu: <path d="M4 7h16M4 12h16M4 17h16"/>,
    flag: <><path d="M5 21V4M5 4l9 2.5L5 12"/><path d="M5 4h11l-2 4 2 4H5"/></>,
    sparkle: <path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8Z"/>,
    note: <><path d="M5 3.5h14a1.5 1.5 0 0 1 1.5 1.5v10L15 20.5H5A1.5 1.5 0 0 1 3.5 19V5A1.5 1.5 0 0 1 5 3.5Z"/><path d="M20 14.5h-4a1 1 0 0 0-1 1v4M7 8h10M7 12h6"/></>
  };

  function Icon({ name, size, style, className, strokeWidth }) {
    const d = P[name];
    if (!d) return null;
    return (
      <svg className={className} width={size || 24} height={size || 24} viewBox="0 0 24 24"
        fill="none" stroke="currentColor" strokeWidth={strokeWidth || 1.7}
        strokeLinecap="round" strokeLinejoin="round" style={style} aria-hidden="true">
        {d}
      </svg>
    );
  }
  window.Icon = Icon;
})();
