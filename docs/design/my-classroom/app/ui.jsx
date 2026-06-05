/* ui.jsx — primitives partagées : Sheet (modale), Field, ClassChip, helpers. */
(function () {
  const { useEffect } = React;
  const Icon = window.Icon;

  /* Couleur d'une classe -> variables CSS */
  function classVars(color) {
    return {
      "--c-tint": `var(--${color}-tint)`,
      "--c-accent": `var(--${color}-accent)`,
      "--c-deep": `var(--${color}-deep)`
    };
  }
  function tintOf(color) { return `var(--${color}-tint)`; }
  function accentOf(color) { return `var(--${color}-accent)`; }
  function deepOf(color) { return `var(--${color}-deep)`; }

  /* Bottom sheet / modale */
  function Sheet({ title, subtitle, onClose, children, maxWidth }) {
    useEffect(() => {
      const onKey = (e) => { if (e.key === "Escape") onClose(); };
      window.addEventListener("keydown", onKey);
      const prev = document.body.style.overflow;
      document.body.style.overflow = "hidden";
      return () => { window.removeEventListener("keydown", onKey); document.body.style.overflow = prev; };
    }, []);
    return (
      <div className="overlay" onMouseDown={(e) => { if (e.target === e.currentTarget) onClose(); }}>
        <div className="sheet" style={maxWidth ? { maxWidth } : null} role="dialog" aria-modal="true">
          <div className="sheet__grip" />
          {title && <h3 className="sheet__title">{title}</h3>}
          {subtitle && <p className="sheet__sub">{subtitle}</p>}
          {children}
        </div>
      </div>
    );
  }

  function Field({ label, children }) {
    return (
      <div className="field">
        {label && <label>{label}</label>}
        {children}
      </div>
    );
  }

  /* Pastille de classe (point + nom, teinte de la classe) */
  function ClassChip({ cls, plain }) {
    if (!cls) return null;
    const style = plain
      ? { background: "transparent", color: "var(--ink)", paddingLeft: 0 }
      : { background: tintOf(cls.color), color: deepOf(cls.color) };
    return (
      <span className="classchip" style={style}>
        <i style={{ background: accentOf(cls.color) }} />
        {cls.name}
      </span>
    );
  }

  /* Format horaire compact */
  function timeRange(start, end) {
    return end ? `${start}\u2009–\u2009${end}` : start;
  }

  window.UI = { Sheet, Field, ClassChip, classVars, tintOf, accentOf, deepOf, timeRange };
})();
