using Microsoft.Xna.Framework;
using Microsoft.Xna.Framework.Graphics;
using System.Collections.Generic;
using Terraria;
using Terraria.GameContent;
using Terraria.ID;
using Terraria.ModLoader;

namespace SummonerReborn.Content.Projectiles
{
	// Bază comună pentru biciurile din mod. Construită după ExampleWhipProjectile din ExampleMod.
	// Imaginea fiecărui bici are, unul sub altul: mânerul (0-26), 3 segmente (26-42, 42-58, 58-74) și vârful (74-92).
	public abstract class BiciProiectilBaza : ModProjectile
	{
		protected virtual int Segmente => 20;
		protected virtual float LungimeMultiplicator => 1f;
		protected virtual Color CuloareFir => Color.White;

		// Ce se întâmplă când biciul lovește un inamic (marca pentru minioni, efecte...).
		protected abstract void LaLovitura(NPC target);

		public override void SetStaticDefaults() {
			ProjectileID.Sets.IsAWhip[Type] = true; // coliziune de bici + merge cu flask-uri
		}

		public override void SetDefaults() {
			Projectile.DefaultToWhip();
			Projectile.WhipSettings.Segments = Segmente;
			Projectile.WhipSettings.RangeMultiplier = LungimeMultiplicator;
		}

		private float Timer => Projectile.ai[0];

		public override void OnHitNPC(NPC target, NPC.HitInfo hit, int damageDone) {
			LaLovitura(target);
			Main.player[Projectile.owner].MinionAttackTargetNPC = target.whoAmI; // minionii atacă inamicul lovit
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
				Color color = Lighting.GetColor(element.ToTileCoordinates(), CuloareFir);
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
				Rectangle frame = new Rectangle(0, 0, 10, 26); // mânerul
				Vector2 origin = new Vector2(5, 8);
				float scale = 1;

				if (i == list.Count - 2) {
					// vârful, care se mărește când biciul e întins la maxim
					frame.Y = 74;
					frame.Height = 18;

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
