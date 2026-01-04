# NewbieGuard 🛡️

**NewbieGuard** is a robust and lightweight protection plugin for PocketMine-MP (API 5.x) designed to enhance the experience of new players and prevent spawn-killing.

The plugin provides a temporary "safe window" for players joining the server, ensuring they can find their way before engaging in combat or world modification.

## 🌟 Key Features

* **🛡️ Automatic Protection:** Automatically grants invulnerability to players upon joining.
* **⚖️ Fair-Play System:** Protection is instantly removed if the player attacks someone else.
* **💾 Persistent Timers:** Remaining protection time is saved in a YAML file, so logging out doesn't "reset" or "waste" the protection.
* **🚫 Interaction Control:** Prevents building and breaking blocks while under protection to maintain balance.
* **💬 Fully Customizable:** All messages and the duration (in minutes) can be easily edited via `config.yml`.
* **🚀 Optimized Performance:** Uses an internal task to handle countdowns efficiently without server lag.

## 🛠️ Installation

1. Download the latest `.phar` from [Poggit](https://poggit.pmmp.io/p/NewbieGuard).
2. Drop it into your server's `plugins/` folder.
3. Restart your server.
4. Customize the settings in `plugin_data/NewbieGuard/config.yml`.

## ⚙️ Configuration

The default configuration provides 5 minutes of safety:

```yaml
# Protection duration in minutes
protection-time: 5

# Messages (Colors supported with §)
msg-protection-start: "§aYou are protected for {TIME} minutes! PvP and building disabled."
msg-protection-end: "§cYour newbie protection has ended!"
msg-protection-removed: "§6You attacked a player! Protection removed."
msg-cant-build: "§cYou cannot build while protected."
msg-cant-pvp: "§cThis player is protected!"
