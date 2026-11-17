// Serveur sans dépendance : sert les deux pages et diffuse la couleur en direct (SSE).
const http = require("http");
const fs = require("fs");
const path = require("path");
const crypto = require("crypto");

const PORT = process.env.PORT || 3000;
const CLE = process.env.CLE || crypto.randomBytes(4).toString("hex");
let couleur = "#1f4fd8";
let alarme = false;
const clients = new Set();

const pages = { "/": "index.html", "/controle": "controle.html" };

const etat = () => `data: ${JSON.stringify({ couleur, alarme })}\n\n`;
function diffuser() {
  for (const res of clients) res.write(etat());
}

http.createServer((req, res) => {
  const url = new URL(req.url, "http://x");

  if (url.pathname === "/events") {
    res.writeHead(200, { "Content-Type": "text/event-stream", "Cache-Control": "no-cache", Connection: "keep-alive" });
    res.write(etat());
    clients.add(res);
    req.on("close", () => clients.delete(res));
    return;
  }

  if ((url.pathname === "/couleur" || url.pathname === "/alarme") && req.method === "POST") {
    let body = "";
    req.on("data", (d) => { body += d; if (body.length > 1000) req.destroy(); });
    req.on("end", () => {
      let data = {};
      try { data = JSON.parse(body); } catch {}
      if (data.cle !== CLE) { res.writeHead(403); return res.end("clé invalide"); }
      if (url.pathname === "/alarme") {
        alarme = data.active === true;
      } else {
        if (!/^#[0-9a-fA-F]{6}$/.test(data.couleur || "")) { res.writeHead(400); return res.end("couleur invalide"); }
        couleur = data.couleur.toLowerCase();
      }
      diffuser();
      res.writeHead(204); res.end();
    });
    return;
  }

  const page = pages[url.pathname];
  if (page && req.method === "GET") {
    res.writeHead(200, { "Content-Type": "text/html; charset=utf-8" });
    return fs.createReadStream(path.join(__dirname, "public", page)).pipe(res);
  }
  res.writeHead(404); res.end("introuvable");
}).listen(PORT, () => {
  console.log(`Afficheur   : http://localhost:${PORT}/`);
  console.log(`Télécommande: http://localhost:${PORT}/controle?cle=${CLE}`);
  console.log(`Clé         : ${CLE}  (à ne donner qu'à ceux qui peuvent changer la couleur)`);
});
