using System.Collections.Generic;
using System.Linq;
using Microsoft.Xna.Framework.Graphics;
using StardewModdingAPI;
using StardewModdingAPI.Events;
using StardewValley;
using StardewValley.GameData.Machines;
using StardewValley.GameData.Objects;
using StardewValley.GameData.Shops;
using StardewValley.GameData.WildTrees;

namespace CookingBook
{
    /// <summary>Punctul de intrare al modului.</summary>
    internal sealed class ModEntry : Mod
    {
        /*********
        ** Constante
        *********/
        private const string BookId = "eddyqqqq.CookingBook_CookingBook";
        private const string QualifiedBookId = "(O)" + BookId;
        private const string BookTextureAsset = "Mods/eddyqqqq.CookingBook/CookingBook";

        private const string WoodId = "(O)388";
        private const string HardwoodId = "(O)709";
        private const string CoalId = "(O)382";
        private const string MahoganySeedId = "(O)292";
        private const string CharcoalKilnId = "(BC)114";
        private const string MahoganyTreeId = "8";

        private ModConfig Config = null!;


        /*********
        ** Entry
        *********/
        public override void Entry(IModHelper helper)
        {
            this.Config = helper.ReadConfig<ModConfig>();

            helper.Events.Content.AssetRequested += this.OnAssetRequested;
            helper.Events.Player.InventoryChanged += this.OnInventoryChanged;
            helper.Events.GameLoop.SaveLoaded += (_, _) => this.ReadBooksInInventory();
        }


        /*********
        ** Modificări la datele jocului
        *********/
        private void OnAssetRequested(object? sender, AssetRequestedEventArgs e)
        {
            // iconița cărții
            if (e.NameWithoutLocale.IsEquivalentTo(BookTextureAsset))
                e.LoadFromModFile<Texture2D>("assets/cooking-book.png", AssetLoadPriority.Medium);

            // obiectul Cooking Book
            else if (e.NameWithoutLocale.IsEquivalentTo("Data/Objects"))
            {
                e.Edit(asset =>
                {
                    var data = asset.AsDictionary<string, ObjectData>().Data;
                    data[BookId] = new ObjectData
                    {
                        Name = "Cooking Book",
                        DisplayName = "Cooking Book",
                        Description = "O carte de bucate uriașă. Când o cumperi, înveți toate rețetele de gătit din joc.",
                        Type = "Basic",
                        Category = 0,
                        Price = 0,
                        Texture = BookTextureAsset,
                        SpriteIndex = 0,
                        ExcludeFromShippingCollection = true,
                        ExcludeFromRandomSale = true
                    };
                });
            }

            // magazinul lui Pierre (General Store)
            else if (e.NameWithoutLocale.IsEquivalentTo("Data/Shops"))
            {
                e.Edit(asset =>
                {
                    var shops = asset.AsDictionary<string, ShopData>().Data;
                    if (!shops.TryGetValue("SeedShop", out ShopData? shop))
                        return;

                    shop.Items.Insert(0, new ShopItemData
                    {
                        Id = BookId,
                        ItemId = QualifiedBookId,
                        Price = this.Config.CookingBookPrice,
                        IgnoreShopPriceModifiers = true // Pierre dublează prețurile; vrem prețul exact din config
                    });
                    shop.Items.Insert(1, new ShopItemData
                    {
                        Id = "eddyqqqq.CookingBook_MahoganySeed",
                        ItemId = MahoganySeedId,
                        Price = this.Config.MahoganySeedPrice,
                        IgnoreShopPriceModifiers = true
                    });
                });
            }

            // Charcoal Kiln: 1 lemn -> 2 cărbuni, 1 hardwood -> 10 cărbuni
            else if (e.NameWithoutLocale.IsEquivalentTo("Data/Machines"))
            {
                e.Edit(asset =>
                {
                    var machines = asset.AsDictionary<string, MachineData>().Data;
                    if (machines.TryGetValue(CharcoalKilnId, out MachineData? kiln))
                        this.EditCharcoalKiln(kiln);
                });
            }

            // copaci: mai mult lemn, mai mult hardwood, Mahogany crește mai repede
            else if (e.NameWithoutLocale.IsEquivalentTo("Data/WildTrees"))
            {
                e.Edit(asset =>
                {
                    var trees = asset.AsDictionary<string, WildTreeData>().Data;
                    foreach ((string id, WildTreeData tree) in trees)
                    {
                        tree.ChopItems ??= new List<WildTreeChopItemData>();

                        if (this.Config.ExtraWoodPerTree > 0)
                            tree.ChopItems.Add(this.CreateChopDrop("ExtraWood", WoodId, this.Config.ExtraWoodPerTree));

                        if (id == MahoganyTreeId)
                        {
                            if (this.Config.ExtraHardwoodPerMahogany > 0)
                                tree.ChopItems.Add(this.CreateChopDrop("ExtraHardwood", HardwoodId, this.Config.ExtraHardwoodPerMahogany));

                            tree.GrowthChance = this.Config.MahoganyGrowthChance;
                            tree.FertilizedGrowthChance = System.Math.Max(tree.FertilizedGrowthChance, this.Config.MahoganyGrowthChance);
                        }
                    }
                });
            }
        }

        /// <summary>Modifică regulile Charcoal Kiln.</summary>
        private void EditCharcoalKiln(MachineData kiln)
        {
            kiln.OutputRules ??= new List<MachineOutputRule>();

            // regula existentă pentru lemn (în joc: 10 lemne -> 1 cărbune)
            MachineOutputRule? woodRule = kiln.OutputRules.FirstOrDefault(rule =>
                rule.Triggers?.Any(trigger => trigger.RequiredItemId == WoodId) == true
            );
            int minutes = woodRule?.MinutesUntilReady ?? 30;

            if (woodRule != null)
                kiln.OutputRules.Remove(woodRule);

            // hardwood primul, ca să nu fie prins de alte reguli
            kiln.OutputRules.Insert(0, this.CreateKilnRule("Hardwood", HardwoodId, this.Config.CoalPerHardwood, minutes));
            kiln.OutputRules.Insert(1, this.CreateKilnRule("Wood", WoodId, this.Config.CoalPerWood, minutes));
        }

        private MachineOutputRule CreateKilnRule(string name, string inputId, int coal, int minutes)
        {
            return new MachineOutputRule
            {
                Id = $"eddyqqqq.CookingBook_{name}ToCoal",
                MinutesUntilReady = minutes,
                Triggers = new List<MachineOutputTriggerRule>
                {
                    new()
                    {
                        Id = $"eddyqqqq.CookingBook_{name}",
                        Trigger = MachineOutputTrigger.ItemPlacedInMachine,
                        RequiredItemId = inputId,
                        RequiredCount = 1
                    }
                },
                OutputItem = new List<MachineItemOutput>
                {
                    new()
                    {
                        Id = $"eddyqqqq.CookingBook_{name}Coal",
                        ItemId = CoalId,
                        MinStack = coal,
                        MaxStack = coal
                    }
                }
            };
        }

        private WildTreeChopItemData CreateChopDrop(string name, string itemId, int count)
        {
            return new WildTreeChopItemData
            {
                Id = $"eddyqqqq.CookingBook_{name}",
                ItemId = itemId,
                MinStack = count,
                MaxStack = count,
                MinSize = WildTreeGrowthStage.Tree,
                ForStump = false
            };
        }


        /*********
        ** Cartea
        *********/
        private void OnInventoryChanged(object? sender, InventoryChangedEventArgs e)
        {
            if (e.IsLocalPlayer && e.Added.Any(item => item.QualifiedItemId == QualifiedBookId))
                this.ReadBooksInInventory();
        }

        /// <summary>Dacă jucătorul are cartea în inventar, o „citește”: o scoate și învață toate rețetele.</summary>
        private void ReadBooksInInventory()
        {
            Farmer player = Game1.player;
            List<Item> books = player.Items.Where(item => item?.QualifiedItemId == QualifiedBookId).ToList();
            if (books.Count == 0)
                return;

            foreach (Item book in books)
                player.removeItemFromInventory(book);

            int learned = 0;
            foreach (string recipe in CraftingRecipe.cookingRecipes.Keys)
            {
                if (!player.cookingRecipes.ContainsKey(recipe))
                {
                    player.cookingRecipes.Add(recipe, 0);
                    learned++;
                }
            }

            if (learned > 0)
            {
                Game1.playSound("newRecipe");
                Game1.addHUDMessage(new HUDMessage($"Ai învățat {learned} rețete noi!", HUDMessage.newQuest_type));
                this.Monitor.Log($"Cooking Book: am învățat {learned} rețete.", LogLevel.Info);
            }
            else
            {
                // știa deja tot: îi dăm banii înapoi
                player.Money += this.Config.CookingBookPrice * books.Sum(book => book.Stack);
                Game1.addHUDMessage(new HUDMessage("Știi deja toate rețetele! Ți-am dat banii înapoi.", HUDMessage.error_type));
            }
        }
    }
}
