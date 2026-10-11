using SummonerReborn.Common.Players;
using Terraria.ModLoader;

namespace SummonerReborn.Common.Commands
{
	// Comanda de chat /kittest: îți dă din nou kit-ul de testare, oricând.
	public class KitCommand : ModCommand
	{
		public override CommandType Type => CommandType.Chat;

		public override string Command => "kittest";

		public override string Usage => "/kittest";

		public override string Description => "Iti da kit-ul de testare pentru summoner.";

		public override void Action(CommandCaller caller, string input, string[] args) {
			caller.Player.GetModPlayer<KitTestarePlayer>().DaKit();
		}
	}
}
