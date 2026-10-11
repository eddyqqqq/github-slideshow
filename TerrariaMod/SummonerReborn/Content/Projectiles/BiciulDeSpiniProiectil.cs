using Microsoft.Xna.Framework;
using Microsoft.Xna.Framework.Graphics;
using SummonerReborn.Content.Buffs;
using System.Collections.Generic;
using Terraria;
using Terraria.GameContent;
using Terraria.ID;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Projectiles
{
	// Partea „care zboară” a biciului. Construit după ExampleWhipProjectile din ExampleMod.
	// Imaginea BiciulDeSpiniProiectil.png are mânerul, 3 segmente și vârful, unul sub altul.
	public class BiciulDeSpiniProiectil : ModProjectile
	{
		public override void SetStaticDefaults() {
			ProjectileID.Sets.IsAWhip[Type] = true; // coliziune de bici + merge cu flask-uri
		}

		public override void SetDefaults() {
			Projectile.DefaultToWhip();
			Projectile.WhipSettings.Segments = 18;
			Projectile.WhipSettings.RangeMultiplier = 1.1f; // puțin mai lung decât Leather Whip
		}

		private float Timer => Projectile.ai[0];

		public override void OnHitNPC(NPC target, NPC.HitInfo hit, int damageDone) {
			// Pune marca Spinilor (4 secunde) și le spune minionilor să atace inamicul ăsta.
			target.AddBuff(ModContent.BuffType<MarcaSpinilor>(), 240);
			Main.player[Projectile.owner].MinionAttackTargetNPC = target.whoAmI;
			Projectile.damage = (int)(Projectile.damage * 0.6f); // fiecare inamic lovit în plus primește mai puțin damage
		}

		// Desenează o linie între segmente, ca să nu rămână goluri.
		private void DrawLine(List<Vector2> list) {
			Texture2D texture = TextureAssets.FishingLine.Value;
			Rectangle frame = texture.Frame();
			Vector2 origin = new Vector2(frame.Width / 2, 2);

			Vector2 pos = list[0];
			for (int i = 0; i < list.Count - 1; i++) {
				Vector2 element = list[i];
				Vector2 diff = list[i + 1] - element;

				float rotation = diff.ToRotation() - MathHelper.PiOver2;
				Color color = Lighting.GetColor(element.ToTileCoordinates(), new Color(90, 160, 60));
				Vector2 scale = new Vector2(1, (diff.Length() + 2) / frame.Height);

				Main.EntitySpriteDraw(texture, pos - Main.screenPosition, frame, color, rotation, origin, scale, SpriteEffects.None, 0);
				pos += diff;
			}
		}

		public override bool PreDraw(ref Color lightColor) {
			List<Vector2> list = new List<Vector2>();
			Projectile.FillWhipControlPoints(Projectile, list);

			DrawLine(list);

			SpriteEffects flip = Projectile.spriteDirection < 0 ? SpriteEffects.None : SpriteEffects.FlipHorizontally;
			Texture2D texture = TextureAssets.Projectile[Type].Value;
			Vector2 pos = list[0];

			for (int i = 0; i < list.Count - 1; i++) {
				// Bucățile din imagine (aceleași măsuri ca în ExampleMod):
				// mâner 0-26, segment 1 26-42, segment 2 42-58, segment 3 58-74, vârf 74-92.
				Rectangle frame = new Rectangle(0, 0, 10, 26);
				Vector2 origin = new Vector2(5, 8);
				float scale = 1;

				if (i == list.Count - 2) {
					frame.Y = 74;
					frame.Height = 18;

					// Vârful se mărește când biciul e întins la maxim.
					Projectile.GetWhipSettings(Projectile, out float timeToFlyOut, out int _, out float _);
					float t = Timer / timeToFlyOut;
					scale = MathHelper.Lerp(0.5f, 1.5f, Utils.GetLerpValue(0.1f, 0.7f, t, true) * Utils.GetLerpValue(0.9f, 0.7f, t, true));
				}
				else if (i > 10) {
					frame.Y = 58;
					frame.Height = 16;
				}
				else if (i > 5) {
					frame.Y = 42;
					frame.Height = 16;
				}
				else if (i > 0) {
					frame.Y = 26;
					frame.Height = 16;
				}

				Vector2 element = list[i];
				Vector2 diff = list[i + 1] - element;

				float rotation = diff.ToRotation() - MathHelper.PiOver2;
				Color color = Lighting.GetColor(element.ToTileCoordinates());

				Main.EntitySpriteDraw(texture, pos - Main.screenPosition, frame, color, rotation, origin, scale, flip, 0);
				pos += diff;
			}
			return false;
		}
	}
}
