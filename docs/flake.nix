
{
  description = "laraduck docs";

  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";
    bun2nix = {
      url = "github:baileyluTCD/bun2nix";
      inputs.nixpkgs.follows = "nixpkgs";
    };
  };

  outputs = {
    self,
    nixpkgs,
    bun2nix,
    ...
  }: let
    systems = [
      "x86_64-linux"
      "x86_64-darwin"
      "aarch64-linux"
      "aarch64-darwin"
    ];

    forAllSystems = nixpkgs.lib.genAttrs systems;
  in {
    devShells = forAllSystems (system: let
      pkgs = nixpkgs.legacyPackages.${system};
    in {
      default = pkgs.mkShell {
        packages = with pkgs; [
          bun
          nodePackages.typescript
          bun2nix.packages.${system}.default
          typescript-language-server
          astro-language-server
        ];
        shellHook = ''
          # Setup the environment
          export PATH=$PWD/node_modules/.bin:$PATH
        '';
      };
    });

    packages = forAllSystems (system: let
      pkgs = nixpkgs.legacyPackages.${system};
    in rec {
      laraduck-docs = pkgs.stdenv.mkDerivation {
        pname = "laraduck-docs";
        version = "0.0.1";
        src = ./.;

        nativeBuildInputs = with pkgs; [
          bun
          nodejs
        ];

        buildPhase = ''
          # Install dependencies
          export HOME=$TMPDIR
          bun install
          
          # Copy examples directory
          cp -r ${./../examples} ./examples
          
          # Build the site with optimized settings
          ASTRO_TELEMETRY_DISABLED=1 bun --bun run build
        '';

        installPhase = ''
          cp -r dist $out
        '';
      };
      default = laraduck-docs;
    });

    apps = forAllSystems (system: let
      pkgs = nixpkgs.legacyPackages.${system};
    in {
      default = {
        type = "app";
        program = "${pkgs.writeShellScript "serve-docs" ''
          ${pkgs.python3}/bin/python3 -m http.server 8000 -d ${self.packages.${system}.default}
        ''}";
      };
    });

    formatter = forAllSystems (system: nixpkgs.legacyPackages.${system}.nixpkgs-fmt);
  };
}
