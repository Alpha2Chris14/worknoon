import { useEffect, useState } from "react";

async function api(path, options = {}) {
  const res = await fetch("/api" + path, {
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    ...options,
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const first = data.errors && Object.values(data.errors)[0]?.[0];
    throw new Error(first || data.message || `Request failed (${res.status})`);
  }
  return data;
}

const Badge = ({ d }) => <span className={`badge ${d}`}>{d}</span>;

function Customer() {
  const [form, setForm] = useState({ email: "", order_id: "", message: "" });
  const [chat, setChat] = useState([]);
  const [orders, setOrders] = useState([]);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");

  useEffect(() => {
    api("/orders")
      .then(setOrders)
      .catch(() => {});
  }, []);
  const set = (k) => (e) => setForm({ ...form, [k]: e.target.value });

  async function submit(e) {
    e.preventDefault();
    setBusy(true);
    setError("");
    const text = form.message;
    setChat((c) => [...c, { me: true, text: `${form.order_id}: ${text}` }]);
    try {
      const r = await api("/refund-requests", {
        method: "POST",
        body: JSON.stringify(form),
      });
      setChat((c) => [...c, { decision: r.decision, text: r.reply }]);
      setForm({ ...form, message: "" });
    } catch (err) {
      setError(err.message);
    }
    setBusy(false);
  }

  return (
    <>
      <div className="card">
        {chat.length > 0 && (
          <div className="chat">
            {chat.map((m, i) => (
              <div key={i} className={`msg ${m.me ? "me" : "bot"}`}>
                {m.decision && (
                  <div>
                    <Badge d={m.decision} />
                  </div>
                )}
                {m.text}
              </div>
            ))}
          </div>
        )}
        <form onSubmit={submit}>
          <div className="row">
            <div className="grow">
              <label>Email</label>
              <input
                required
                value={form.email}
                onChange={set("email")}
                placeholder="you@example.com"
              />
            </div>
            <div className="grow">
              <label>Order ID</label>
              <input
                required
                value={form.order_id}
                onChange={set("order_id")}
                placeholder="ORD-1001"
              />
            </div>
          </div>
          <div style={{ margin: "10px 0" }}>
            <label>Describe your issue</label>
            <textarea
              required
              rows={3}
              maxLength={1000}
              value={form.message}
              onChange={set("message")}
              placeholder="e.g. The headphones arrived cracked."
            />
          </div>
          {error && <p className="err">{error}</p>}
          <button className="primary" disabled={busy}>
            {busy ? "Reviewing…" : "Submit request"}
          </button>
        </form>
      </div>

      <div className="card">
        <strong>Test data</strong>{" "}
        <span className="muted">— click a row to fill the form</span>
        <div className="scroll">
          <table>
            <thead>
              <tr>
                <th>Order</th>
                <th>Email</th>
                <th>Item</th>
                <th>Total</th>
                <th>Placed</th>
                <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              {orders.map((o) => (
                <tr
                  key={o.order_id}
                  className="click"
                  onClick={() =>
                    setForm({ ...form, email: o.email, order_id: o.order_id })
                  }
                >
                  <td>{o.order_id}</td>
                  <td>{o.email}</td>
                  <td>{o.item}</td>
                  <td>${Number(o.total).toFixed(2)}</td>
                  <td>{o.placed_at}</td>
                  <td>
                    {[
                      o.final_sale && "final sale",
                      o.status !== "delivered" && o.status,
                    ]
                      .filter(Boolean)
                      .join(", ")}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </>
  );
}

function Admin() {
  const [rows, setRows] = useState([]);
  const [open, setOpen] = useState(null);
  const [error, setError] = useState("");

  useEffect(() => {
    const load = () =>
      api("/refund-requests")
        .then(setRows)
        .catch((e) => setError(e.message));
    load();
    const t = setInterval(load, 5000);
    return () => clearInterval(t);
  }, []);

  const counts = rows.reduce(
    (a, r) => ({ ...a, [r.decision]: (a[r.decision] || 0) + 1 }),
    {},
  );

  return (
    <div className="card">
      <div className="row" style={{ marginBottom: 10 }}>
        {["APPROVED", "DENIED", "ESCALATED"].map((d) => (
          <span key={d}>
            <Badge d={d} /> {counts[d] || 0}
          </span>
        ))}
        <span className="muted">
          Auto-refreshes every 5s · click a row for the audit trail
        </span>
      </div>
      {error && <p className="err">{error}</p>}
      <div className="scroll">
        <table>
          <thead>
            <tr>
              <th>Time</th>
              <th>Order</th>
              <th>Customer</th>
              <th>Decision</th>
              <th>Category</th>
              <th>AI</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((r) => (
              <>
                <tr
                  key={r.id}
                  className="click"
                  onClick={() => setOpen(open === r.id ? null : r.id)}
                >
                  <td>{new Date(r.created_at).toLocaleString()}</td>
                  <td>{r.order_id}</td>
                  <td>{r.email}</td>
                  <td>
                    <Badge d={r.decision} />
                  </td>
                  <td>{r.reason_category}</td>
                  <td>{r.llm_used ? "Gemini" : "Fallback"}</td>
                </tr>
                {open === r.id && (
                  <tr key={r.id + "d"}>
                    <td colSpan={6}>
                      <p>
                        <strong>Customer message:</strong> {r.message}
                      </p>
                      <strong>Rules / reasoning:</strong>
                      <ul>
                        {r.rules_fired.map((x, i) => (
                          <li key={i}>{x}</li>
                        ))}
                      </ul>
                      <p>
                        <strong>AI analysis:</strong> {r.llm_analysis.summary}
                        {r.llm_analysis.injection_attempt &&
                          " ⚠ injection attempt flagged"}
                        {r.llm_analysis.suspicious && " ⚠ flagged suspicious"}
                      </p>
                      <p>
                        <strong>Reply sent:</strong> {r.reply}
                      </p>
                    </td>
                  </tr>
                )}
              </>
            ))}
            {rows.length === 0 && (
              <tr>
                <td colSpan={6} className="muted">
                  No requests yet.
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}

export default function App() {
  const [tab, setTab] = useState("customer");
  return (
    <div className="wrap">
      <h1>Refund Support</h1>
      <div className="tabs">
        <button
          className={tab === "customer" ? "on" : ""}
          onClick={() => setTab("customer")}
        >
          Customer
        </button>
        <button
          className={tab === "admin" ? "on" : ""}
          onClick={() => setTab("admin")}
        >
          Support dashboard
        </button>
      </div>
      {tab === "customer" ? <Customer /> : <Admin />}
    </div>
  );
}
