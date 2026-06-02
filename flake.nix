{
  description = "Development environment for the Typesense Laravel Scout driver";

  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixpkgs-unstable";
  };

  outputs = { self, nixpkgs }:
    let
      systems = [
        "aarch64-darwin"
        "aarch64-linux"
        "x86_64-darwin"
        "x86_64-linux"
      ];

      forAllSystems = f:
        nixpkgs.lib.genAttrs systems (system:
          f (import nixpkgs {
            inherit system;
          }));
    in
    {
      devShells = forAllSystems (pkgs:
        {
          default = pkgs.mkShell {
            packages = [
              pkgs.curl
              pkgs.git
              pkgs.php83
              pkgs.php83Packages.composer
            ];

            shellHook = ''
              echo "PHP:      $(php --version | head -n 1)"
              echo "Composer: $(composer --version)"
              echo
              echo "Install dependencies: composer update --prefer-dist --no-interaction --no-progress"
              echo "Run tests:            vendor/bin/phpunit tests"
            '';
          };
        });

      apps = forAllSystems (pkgs:
        let
          install = pkgs.writeShellApplication {
            name = "scout-driver-install";
            runtimeInputs = [
              pkgs.php83
              pkgs.php83Packages.composer
            ];
            text = ''
              composer update --prefer-dist --no-interaction --no-progress
            '';
          };

          test = pkgs.writeShellApplication {
            name = "scout-driver-test";
            runtimeInputs = [
              pkgs.php83
              pkgs.php83Packages.composer
            ];
            text = ''
              if [ ! -x vendor/bin/phpunit ]; then
                composer update --prefer-dist --no-interaction --no-progress
              fi

              vendor/bin/phpunit tests
            '';
          };
        in
        {
          install = {
            type = "app";
            program = "${install}/bin/scout-driver-install";
          };

          test = {
            type = "app";
            program = "${test}/bin/scout-driver-test";
          };
        });
    };
}
