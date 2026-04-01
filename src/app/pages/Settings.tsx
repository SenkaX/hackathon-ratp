import { Settings as SettingsIcon, Bell, Lock, Database, Zap, Camera, QrCode, Twitter } from 'lucide-react';

export function Settings() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Paramètres</h1>
        <p className="text-gray-600 mt-1">Configuration de la plateforme</p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Notifications */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div className="flex items-center gap-3 mb-4">
            <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
              <Bell className="w-5 h-5 text-blue-600" />
            </div>
            <h3 className="text-lg font-semibold">Notifications</h3>
          </div>
          <div className="space-y-3">
            <label className="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg cursor-pointer">
              <span className="text-sm">Tickets critiques</span>
              <input type="checkbox" defaultChecked className="w-4 h-4" />
            </label>
            <label className="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg cursor-pointer">
              <span className="text-sm">Nouveaux tickets IA</span>
              <input type="checkbox" defaultChecked className="w-4 h-4" />
            </label>
            <label className="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg cursor-pointer">
              <span className="text-sm">Tickets assignés</span>
              <input type="checkbox" defaultChecked className="w-4 h-4" />
            </label>
            <label className="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg cursor-pointer">
              <span className="text-sm">Points chauds détectés</span>
              <input type="checkbox" className="w-4 h-4" />
            </label>
          </div>
        </div>

        {/* Sécurité et RGPD */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div className="flex items-center gap-3 mb-4">
            <div className="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
              <Lock className="w-5 h-5 text-green-600" />
            </div>
            <h3 className="text-lg font-semibold">Sécurité & RGPD</h3>
          </div>
          <div className="space-y-3">
            <div className="p-3 bg-green-50 border border-green-200 rounded-lg">
              <p className="text-sm text-green-800">
                ✅ Conformité RGPD activée
              </p>
            </div>
            <label className="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg cursor-pointer">
              <span className="text-sm">Anonymisation automatique</span>
              <input type="checkbox" defaultChecked className="w-4 h-4" />
            </label>
            <label className="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg cursor-pointer">
              <span className="text-sm">Logs d'audit</span>
              <input type="checkbox" defaultChecked className="w-4 h-4" />
            </label>
            <label className="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg cursor-pointer">
              <span className="text-sm">Validation humaine obligatoire</span>
              <input type="checkbox" defaultChecked disabled className="w-4 h-4" />
            </label>
          </div>
        </div>

        {/* Sources de données */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div className="flex items-center gap-3 mb-4">
            <div className="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
              <Database className="w-5 h-5 text-purple-600" />
            </div>
            <h3 className="text-lg font-semibold">Sources de données</h3>
          </div>
          <div className="space-y-3">
            <div className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
              <div className="flex items-center gap-3">
                <Camera className="w-4 h-4 text-gray-600" />
                <span className="text-sm">Caméras bus</span>
              </div>
              <span className="text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full">Actif</span>
            </div>
            <div className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
              <div className="flex items-center gap-3">
                <QrCode className="w-4 h-4 text-gray-600" />
                <span className="text-sm">QR Codes</span>
              </div>
              <span className="text-xs px-2 py-1 bg-green-100 text-green-700 rounded-full">Actif</span>
            </div>
            <div className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
              <div className="flex items-center gap-3">
                <Twitter className="w-4 h-4 text-gray-600" />
                <span className="text-sm">Réseaux sociaux (Scraping)</span>
              </div>
              <span className="text-xs px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full">Test</span>
            </div>
          </div>
        </div>

        {/* IA et Automatisation */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div className="flex items-center gap-3 mb-4">
            <div className="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
              <Zap className="w-5 h-5 text-orange-600" />
            </div>
            <h3 className="text-lg font-semibold">IA & Automatisation</h3>
          </div>
          <div className="space-y-3">
            <label className="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg cursor-pointer">
              <span className="text-sm">Création auto de tickets</span>
              <input type="checkbox" defaultChecked className="w-4 h-4" />
            </label>
            <label className="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg cursor-pointer">
              <span className="text-sm">Résumés IA</span>
              <input type="checkbox" defaultChecked className="w-4 h-4" />
            </label>
            <label className="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg cursor-pointer">
              <span className="text-sm">Fusion automatique des doublons</span>
              <input type="checkbox" defaultChecked className="w-4 h-4" />
            </label>
            <label className="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg cursor-pointer">
              <span className="text-sm">Détection points chauds</span>
              <input type="checkbox" defaultChecked className="w-4 h-4" />
            </label>
            <label className="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg cursor-pointer">
              <span className="text-sm">Indice de confiance</span>
              <input type="checkbox" defaultChecked className="w-4 h-4" />
            </label>
          </div>
        </div>
      </div>

      {/* Indice de confiance */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div className="flex items-center gap-3 mb-4">
          <SettingsIcon className="w-5 h-5 text-gray-600" />
          <h3 className="text-lg font-semibold">Configuration de l'indice de confiance</h3>
        </div>
        <p className="text-sm text-gray-600 mb-4">
          L'indice de confiance permet de prioriser les tickets en fonction de la fiabilité de la source
          et de l'historique du rapporteur. Il n'impacte pas les droits des utilisateurs mais aide à la
          priorisation interne.
        </p>
        <div className="space-y-4">
          <div>
            <label className="text-sm font-medium text-gray-700 block mb-2">
              Seuil de confiance élevée (priorisation haute)
            </label>
            <input
              type="range"
              min="0"
              max="100"
              defaultValue="80"
              className="w-full"
            />
            <div className="flex justify-between text-xs text-gray-500 mt-1">
              <span>0%</span>
              <span className="font-medium">80%</span>
              <span>100%</span>
            </div>
          </div>
          <div>
            <label className="text-sm font-medium text-gray-700 block mb-2">
              Seuil de confiance moyenne
            </label>
            <input
              type="range"
              min="0"
              max="100"
              defaultValue="60"
              className="w-full"
            />
            <div className="flex justify-between text-xs text-gray-500 mt-1">
              <span>0%</span>
              <span className="font-medium">60%</span>
              <span>100%</span>
            </div>
          </div>
          <div>
            <label className="text-sm font-medium text-gray-700 block mb-2">
              Pénalité par faux signalement
            </label>
            <input
              type="number"
              defaultValue="10"
              min="0"
              max="50"
              className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            <p className="text-xs text-gray-500 mt-1">Points retirés pour chaque signalement non retenu</p>
          </div>
        </div>
      </div>

      {/* Informations système */}
      <div className="bg-gradient-to-br from-blue-50 to-purple-50 rounded-lg border border-blue-200 p-6">
        <h3 className="font-semibold mb-3">📊 Informations système</h3>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <p className="text-xs text-gray-600 mb-1">Version</p>
            <p className="font-semibold">v1.0.0 (Prototype)</p>
          </div>
          <div>
            <p className="text-xs text-gray-600 mb-1">Environnement</p>
            <p className="font-semibold">Développement</p>
          </div>
          <div>
            <p className="text-xs text-gray-600 mb-1">Tickets traités</p>
            <p className="font-semibold">10</p>
          </div>
          <div>
            <p className="text-xs text-gray-600 mb-1">Uptime</p>
            <p className="font-semibold">99.9%</p>
          </div>
        </div>
      </div>

      <div className="flex justify-end gap-3">
        <button className="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
          Réinitialiser
        </button>
        <button className="px-6 py-2 bg-[#2f4c99] text-white rounded-lg hover:bg-[#253a75] transition-colors">
          Sauvegarder les modifications
        </button>
      </div>
    </div>
  );
}